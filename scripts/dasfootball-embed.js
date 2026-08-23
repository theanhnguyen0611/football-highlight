#!/usr/bin/env node
/**
 * Lấy embed URL từ dasfootball.com (Next.js App Router, JS-rendered)
 * Usage: node scripts/dasfootball-embed.js <page_url>
 * Output: JSON { embedUrl: "...", type: "youtube|hls|streamable|iframe" }
 */
import { chromium } from 'playwright';

const pageUrl = process.argv[2];
if (!pageUrl) {
    console.log(JSON.stringify({ error: 'No URL provided' }));
    process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    locale: 'en-US',
});

const page = await context.newPage();

let result = null;

// Intercept network requests to catch video URLs early (exclude ads/tracking)
const AD_DOMAINS = ['grow.me', 'consentmanager', 'googletagmanager', 'doubleclick', 'scriptwrapper'];
const interceptedUrls = [];
page.on('request', req => {
    const url = req.url();
    if (AD_DOMAINS.some(d => url.includes(d))) return;
    if (url.includes('youtube.com/embed') || url.includes('youtu.be') ||
        url.includes('.m3u8') || url.includes('streamable.com/e/') ||
        url.includes('videas.fr') || url.includes('ok.ru/videoembed')) {
        interceptedUrls.push(url);
    }
});

try {
    await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForTimeout(3000);

    // Phát hiện trang "not found" của DasFootball SPA (HTTP luôn trả 200)
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

    // 1. Check intercepted network requests first
    if (interceptedUrls.length > 0) {
        for (const url of interceptedUrls) {
            if (url.includes('.m3u8')) {
                result = { embedUrl: url, type: 'hls' };
                break;
            }
            if (url.includes('youtube.com/embed') || url.includes('youtu.be')) {
                result = { embedUrl: url, type: 'youtube' };
                break;
            }
            if (url.includes('streamable.com')) {
                result = { embedUrl: url, type: 'streamable' };
                break;
            }
            if (!result) result = { embedUrl: url, type: 'iframe' };
        }
    }

    // 2. Check DOM for iframes / video sources (exclude ad/tracking iframes)
    const AD_DOMAINS = ['grow.me', 'consentmanager', 'googletagmanager', 'doubleclick', 'scriptwrapper', 'gdpr'];
    if (!result) {
        result = await page.evaluate((adDomains) => {
            const VIDEO_SELECTORS = [
                'iframe[src*="youtube.com/embed"]',
                'iframe[src*="youtu.be"]',
                'iframe[src*="streamable.com"]',
                'iframe[src*="videas"]',
                'iframe[src*="ok.ru/videoembed"]',
                'video source[src]',
                'video[src]',
            ];
            for (const sel of VIDEO_SELECTORS) {
                const el = document.querySelector(sel);
                if (!el) continue;
                const src = el.src || el.getAttribute('src');
                if (!src || src === window.location.href) continue;
                if (adDomains.some(d => src.includes(d))) continue;
                let type = 'iframe';
                if (src.includes('youtube') || src.includes('youtu.be')) type = 'youtube';
                else if (src.includes('streamable')) type = 'streamable';
                else if (src.includes('.m3u8')) type = 'hls';
                return { embedUrl: src, type };
            }
            return null;
        }, AD_DOMAINS);
    }

    // 3. Check JSON-LD or structured data in page
    if (!result) {
        result = await page.evaluate(() => {
            const scripts = document.querySelectorAll('script[type="application/ld+json"]');
            for (const script of scripts) {
                try {
                    const data = JSON.parse(script.textContent);
                    const items = Array.isArray(data) ? data : [data];
                    for (const item of items) {
                        if (item.embedUrl) return { embedUrl: item.embedUrl, type: 'iframe' };
                        if (item.contentUrl) return { embedUrl: item.contentUrl, type: 'iframe' };
                    }
                } catch {}
            }
            return null;
        });
    }

} catch (err) {
    console.log(JSON.stringify({ error: err.message }));
    await browser.close();
    process.exit(1);
}

await browser.close();

if (result) {
    console.log(JSON.stringify(result));
} else {
    console.log(JSON.stringify({ error: 'No embed URL found' }));
    process.exit(1);
}
