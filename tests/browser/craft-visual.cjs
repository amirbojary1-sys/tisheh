const { chromium } = require("playwright");
const fs = require("fs");
const base = process.env.BASE_URL || "http://127.0.0.1:8001";
const screenshotDir =
    process.env.QA_SCREENSHOTS ||
    require("path").join(__dirname, "screenshots");
fs.mkdirSync(screenshotDir, { recursive: true });
(async () => {
    const browser = await chromium.launch({ headless: true });
    let failures = [];
    for (const width of [390, 1440, 768, 320]) {
        const page = await browser.newPage({
            viewport: { width, height: 900 },
        });
        page.on("pageerror", (e) => failures.push(String(e)));
        page.on("console", (m) => {
            if (m.type() === "error") failures.push(m.text());
        });
        for (const path of [
            "/",
            "/login",
            "/register",
            "/jobs",
            "/materials",
            "/materials/1",
        ]) {
            let res = await page.goto(base + path);
            await page.waitForTimeout(150);
            const issues = await page.evaluate(() => ({
                width: innerWidth,
                scroll: document.documentElement.scrollWidth,
                broken: [...document.images]
                    .filter((i) => i.complete && i.naturalWidth === 0)
                    .map((i) => i.src),
                style: getComputedStyle(document.body).backgroundColor,
            }));
            console.log(width, path, res.status(), JSON.stringify(issues));
            if (
                (width === 390 &&
                    [
                        "/",
                        "/login",
                        "/register",
                        "/jobs",
                        "/materials",
                        "/materials/1",
                    ].includes(path)) ||
                (width === 1440 && ["/", "/login"].includes(path))
            )
                await page.screenshot({
                    path:
                        screenshotDir +
                        "/craft-" +
                        width +
                        "-" +
                        (path.replaceAll("/", "") || "home") +
                        ".png",
                    fullPage: true,
                });
        }
        await page.close();
    }
    console.log("BROWSER_ERRORS", JSON.stringify([...new Set(failures)]));
    await browser.close();
})();
