#!/usr/bin/env node
/**
 * Lấy embed URL từ dasfootball.com (Next.js App Router, JS-rendered)
 * Usage: node scripts/dasfootball-embed.js <page_url>
 * Output: JSON { embedUrl, type, sources: [{url,type}] }
 *
 * Một trang DasFootball có thể lộ nhiều nguồn cùng lúc (file trực tiếp trên
 * cdn.videas.fr / cdn.streamain.com, và một video YouTube). Script gom HẾT
 * rồi mới chọn theo thứ hạng, thay vì trả về cái đầu tiên bắt gặp.
 *
 * YouTube xếp CHÓT: từ IP datacenter (Hetzner, OVH) YouTube trả
 * "Sign in to confirm you're not a bot" nên yt-dlp không tải được — đã kiểm
 * chứng trên cả web server lẫn proxy UK. Chỉ dùng khi không còn nguồn nào khác.
 */
import { chromium } from 'playwright';

const pageUrl = process.argv[2];
if (!pageUrl) {
    console.log(JSON.stringify({ error: 'No URL provided' }));
    process.exit(1);
}

// Thứ tự = độ ưu tiên. Càng trên càng rẻ và càng chắc tải được.
const KINDS = [
    { type: 'hls',        rank: 1, re: /\.m3u8(\?|$)/i },
    { type: 'mp4',        rank: 2, re: /\.mp4(\?|$)/i },
    { type: 'streamable', rank: 3, re: /streamable\.com/i },
    { type: 'iframe',     rank: 4, re: /ok\.ru\/videoembed|videas\.fr|dailymotion|vk\.com\/video_ext/i },
    { type: 'youtube',    rank: 5, re: /youtube\.com\/embed|youtu\.be\//i },
];

const AD_DOMAINS = ['grow.me', 'consentmanager', 'googletagmanager', 'doubleclick', 'scriptwrapper', 'gdpr', 'clvrads'];

function classify(url) {
    if (!url || !url.startsWith('http')) return null;
    if (AD_DOMAINS.some(d => url.includes(d))) return null;
    const hit = KINDS.find(k => k.re.test(url));
    return hit ? { url, type: hit.type, rank: hit.rank } : null;
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    locale: 'en-US',
});
const page = await context.newPage();

const found = new Map();               // url -> {url,type,rank}
const add = (url) => {
    const c = classify(url);
    if (c && !found.has(c.url)) found.set(c.url, c);
};

page.on('request', req => add(req.url()));

try {
    await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForTimeout(4000);

    // DasFootball là SPA, trang không tồn tại vẫn trả HTTP 200
    const notFound = await page.evaluate(() =>
        document.body?.innerText?.includes('This page is not available') ||
        document.body?.innerText?.includes('NOT FOUND') ||
        document.title?.includes('not found')
    );
    if (notFound) {
        console.log(JSON.stringify({ error: 'Page not found on dasfootball' }));
        await browser.close();
        process.exit(1);
    }

    // DOM: iframe + thẻ video
    for (const src of await page.evaluate(() => [
        ...[...document.querySelectorAll('iframe')].map(f => f.src || f.getAttribute('src')),
        ...[...document.querySelectorAll('video[src], video source[src]')].map(v => v.src || v.getAttribute('src')),
    ].filter(Boolean))) add(src);

    // JSON-LD VideoObject — nơi DasFootball đặt contentUrl thật
    for (const u of await page.evaluate(() => {
        const out = [];
        for (const s of document.querySelectorAll('script[type="application/ld+json"]')) {
            try {
                const data = JSON.parse(s.textContent);
                for (const item of (Array.isArray(data) ? data : [data])) {
                    if (item.contentUrl) out.push(item.contentUrl);
                    if (item.embedUrl) out.push(item.embedUrl);
                }
            } catch {}
        }
        return out;
    })) add(u);

    // Payload Next.js (self.__next_f.push) — nguồn hay chỉ nằm ở đây
    for (const u of await page.evaluate(() => {
        const text = [...document.querySelectorAll('script')].map(s => s.textContent || '').join('\n');
        return (text.match(/https?:\\?\/\\?\/[^\s"'\\)<>]+/g) || []).map(x => x.replace(/\\\//g, '/'));
    })) add(u);

} catch (err) {
    console.log(JSON.stringify({ error: err.message }));
    await browser.close();
    process.exit(1);
}

await browser.close();

const sources = [...found.values()].sort((a, b) => a.rank - b.rank);

if (!sources.length) {
    console.log(JSON.stringify({ error: 'No embed URL found' }));
    process.exit(1);
}

const best = sources[0];
console.log(JSON.stringify({
    embedUrl: best.url,
    type: best.type,
    sources: sources.map(({ url, type }) => ({ url, type })),
}));
