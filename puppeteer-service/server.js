'use strict';

const express = require('express');
const puppeteer = require('puppeteer');

const app = express();
const PORT = process.env.PORT || 3002;

// Accept large HTML payloads (reports can be ~500KB)
app.use(express.json({ limit: '10mb' }));

/**
 * POST /render
 * Body: { html: string }
 * Returns: application/pdf binary
 *
 * Launches a headless Chromium instance, loads the HTML, and returns the
 * rendered PDF as a binary response. A new browser instance is created per
 * request to avoid state leakage between reports.
 */
app.post('/render', async (req, res) => {
    const { html } = req.body;

    if (typeof html !== 'string' || html.trim().length === 0) {
        return res.status(400).json({ error: 'html field is required' });
    }

    let browser;
    try {
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
            ],
        });

        const page = await browser.newPage();

        // Set content and wait until network is idle (fonts, images loaded)
        await page.setContent(html, { waitUntil: 'networkidle0', timeout: 30000 });

        const pdf = await page.pdf({
            format: 'A4',
            printBackground: true,
            margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' },
        });

        await browser.close();

        res.set('Content-Type', 'application/pdf');
        res.set('Content-Length', pdf.length);
        res.send(pdf);
    } catch (err) {
        if (browser) {
            await browser.close().catch(() => { });
        }
        console.error('[render] error:', err.message);
        res.status(500).json({ error: err.message });
    }
});

// Health check — used by Laravel to verify the service is up
app.get('/health', (_req, res) => {
    res.json({ status: 'ok' });
});

app.listen(PORT, '127.0.0.1', () => {
    console.log(`ReviveGuard Puppeteer PDF service running on http://127.0.0.1:${PORT}`);
});
