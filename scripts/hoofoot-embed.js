#!/usr/bin/env node
/**
 * Lấy embed URL từ hoofoot match page bằng Playwright
 * Usage: node scripts/hoofoot-embed.js <match_url>
 * Output: JSON { embedUrl: "...", hasExtended: bool } hoặc { error: "..." }
 *
 * Hoofoot pre-loads đúng video cho mỗi ?match= URL trong page HTML.
 * Chỉ cần grab initial iframe src — không cần gọi recargar().
 *
 * hasExtended: true nếu trang đã có tab "EXTENDED" (bản dài, thường là
 * video mặc định load sẵn khi có). Hoofoot đôi khi publish bản ngắn
 * (HL-EN/HL-FR...) trước, vài giờ sau mới thêm bản EXTENDED.
 */
import { chromium } from 'playwright';

const matchUrl = process.argv[2];
if (!matchUrl) {
    console.log(JSON.stringify({ error: 'No URL provided' }));
    process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    locale: 'en-US',
});

const page = await context.newPage();

let embedUrl = null;

try {
    await page.goto(matchUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Đợi Cloudflare challenge pass (nếu có) + iframe load
    await page.waitForTimeout(2000);

    // Lấy iframe src trực tiếp từ HTML — hoofoot server-side renders đúng video cho ?match=
    embedUrl = await page.evaluate(() => {
        const selectors = ['#video iframe', '#player iframe', '.video-container iframe', 'iframe[src*="videas"]', 'iframe[src*="streamable"]', 'iframe[src]'];
        for (const sel of selectors) {
            const el = document.querySelector(sel);
            if (el?.src) return el.src;
        }
        return null;
    });

} catch (err) {
    console.log(JSON.stringify({ error: err.message }));
    await browser.close();
    process.exit(1);
}

const hasExtended = await page.evaluate(() => {
    const desc = document.querySelector('#descruta');
    return desc ? /EXTENDED/i.test(desc.textContent) : false;
}).catch(() => false);

await browser.close();

if (embedUrl) {
    console.log(JSON.stringify({ embedUrl, hasExtended }));
} else {
    console.log(JSON.stringify({ error: 'No embed URL found' }));
    process.exit(1);
}
