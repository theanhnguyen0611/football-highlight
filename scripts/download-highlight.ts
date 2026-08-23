#!/usr/bin/env -S deno run --allow-net --allow-run --allow-write --allow-read
/**
 * Pick 720p (or next lower) from an embed/HLS URL, then let ffmpeg
 * download + remux into a proper local HLS tree.
 *
 * Usage:
 *   deno run --allow-net --allow-run --allow-write --allow-read \
 *     download-highlight.ts <embed_url_or_hls_url> <output_dir>
 *
 * Output structure:
 *   <output_dir>/720p/seg00000.ts ...
 *   <output_dir>/720p/index.m3u8
 *   <output_dir>/master.m3u8
 */

const UA =
  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 " +
  "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";

// ─── HTTP helpers ──────────────────────────────────────────────────────────

async function fetchText(url: string, referer = "https://hoofoot.com/"): Promise<string> {
  const res = await fetch(url, {
    headers: { "User-Agent": UA, "Referer": referer, "Accept": "*/*" },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}: ${url}`);
  return res.text();
}

// ─── Embed → master HLS URL ────────────────────────────────────────────────

async function extractHlsFromEmbed(embedUrl: string): Promise<string> {
  console.log(`Fetching embed: ${embedUrl}`);
  const html = await fetchText(embedUrl);
  const m = html.match(/https?:\/\/[^"'\s]+\.m3u8[^"'\s]*/);
  if (!m) throw new Error("No .m3u8 URL found in embed page");
  return m[0];
}

// ─── Stream types ──────────────────────────────────────────────────────────

interface Stream {
  name: string;
  resolution: string;
  bandwidth: number;
  url: string;
}

// ─── Master playlist parser ────────────────────────────────────────────────

function parseMasterPlaylist(content: string, baseUrl: string): Stream[] {
  const lines = content.split("\n");
  const streams: Stream[] = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line.startsWith("#EXT-X-STREAM-INF")) continue;

    const bw   = line.match(/BANDWIDTH=(\d+)/)?.[1] ?? "0";
    const res  = line.match(/RESOLUTION=(\d+x\d+)/)?.[1] ?? "unknown";
    const name = line.match(/NAME="([^"]+)"/)?.[1] ?? `${res}`;
    const uri  = lines[i + 1]?.trim();
    if (!uri) continue;

    streams.push({
      name,
      resolution: res,
      bandwidth: parseInt(bw),
      url: uri.startsWith("http") ? uri : new URL(uri, baseUrl).href,
    });
    i++;
  }

  return streams.sort((a, b) => b.bandwidth - a.bandwidth);
}

// ─── Quality picker: 720p → next lower → lowest ───────────────────────────

function resolutionHeight(res: string): number {
  const m = res.match(/\d+x(\d+)/);
  return m ? parseInt(m[1]) : 0;
}

function pickQuality(streams: Stream[]): Stream {
  const TARGET = 720;

  const exact = streams.find(
    (s) => s.name === "720p" || resolutionHeight(s.resolution) === TARGET,
  );
  if (exact) return exact;

  // Next lower: highest quality still below 720 (streams sorted desc)
  const lower = streams.filter((s) => resolutionHeight(s.resolution) < TARGET);
  if (lower.length > 0) return lower[0];

  return streams[streams.length - 1];
}

// ─── ffmpeg: download HLS stream → local HLS segments ─────────────────────

async function convertToHls(
  streamUrl: string,
  outDir: string,
  referer: string,
): Promise<void> {
  await Deno.mkdir(outDir, { recursive: true });

  const segPattern = `${outDir}/seg%05d.ts`;
  const indexM3u8  = `${outDir}/index.m3u8`;

  const cmd = new Deno.Command("ffmpeg", {
    args: [
      "-y",
      "-loglevel", "error",
      "-user_agent", UA,
      "-headers", `Referer: ${referer}\r\n`,
      "-i", streamUrl,
      "-c", "copy",
      "-hls_time", "10",
      "-hls_list_size", "0",
      "-hls_segment_filename", segPattern,
      indexM3u8,
    ],
    stdout: "inherit",
    stderr: "inherit",
  });

  const { code } = await cmd.output();
  if (code !== 0) throw new Error(`ffmpeg exited with code ${code}`);
}

// ─── Size helper ───────────────────────────────────────────────────────────

async function dirSizeMb(dir: string): Promise<number> {
  let total = 0;
  for await (const entry of Deno.readDir(dir)) {
    if (entry.isFile) {
      const stat = await Deno.stat(`${dir}/${entry.name}`);
      total += stat.size;
    }
  }
  return total / 1024 / 1024;
}

// ─── Main ──────────────────────────────────────────────────────────────────

async function main() {
  const inputUrl = Deno.args[0];
  const outBase  = Deno.args[1];

  if (!inputUrl || !outBase) {
    console.error("Usage: download-highlight.ts <embed_url_or_hls_url> <output_dir>");
    Deno.exit(1);
  }

  // 1. Resolve master HLS URL
  let masterUrl: string;
  if (inputUrl.includes(".m3u8")) {
    masterUrl = inputUrl;
  } else {
    masterUrl = await extractHlsFromEmbed(inputUrl);
    console.log(`HLS master: ${masterUrl}`);
  }

  // 2. Parse master playlist → list streams
  const masterBase    = masterUrl.substring(0, masterUrl.lastIndexOf("/") + 1);
  const masterContent = await fetchText(masterUrl, masterBase);
  const streams       = parseMasterPlaylist(masterContent, masterBase);

  if (streams.length === 0) {
    // Already a single-stream playlist — use directly
    streams.push({ name: "default", resolution: "unknown", bandwidth: 0, url: masterUrl });
  }

  console.log("\nAvailable qualities:");
  for (const s of streams) {
    const mbps = (s.bandwidth / 1_000_000).toFixed(1);
    console.log(`  ${s.name.padEnd(8)} ${s.resolution.padEnd(12)} ${mbps} Mbps`);
  }

  // 3. Pick quality
  const chosen = pickQuality(streams);
  console.log(`\nSelected: ${chosen.name} (${chosen.resolution})`);

  // 4. Download + convert via ffmpeg
  const segDir   = `${outBase}/${chosen.name}`;
  const referer  = masterBase;

  console.log(`Converting to HLS → ${segDir}`);
  await convertToHls(chosen.url, segDir, referer);

  // 5. Write master.m3u8 pointing to local quality dir
  const masterLocal =
    `#EXTM3U\n` +
    `#EXT-X-VERSION:3\n` +
    `#EXT-X-STREAM-INF:BANDWIDTH=${chosen.bandwidth},RESOLUTION=${chosen.resolution}\n` +
    `${chosen.name}/index.m3u8\n`;
  await Deno.writeTextFile(`${outBase}/master.m3u8`, masterLocal);

  // 6. Report
  const sizeMb = await dirSizeMb(segDir);
  console.log(`\nDone! ${sizeMb.toFixed(1)} MB → ${outBase}/master.m3u8`);
}

main();
