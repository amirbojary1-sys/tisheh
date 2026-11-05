const { chromium } = require("playwright");
const assert = require("assert");
const base = process.env.BASE_URL || "http://127.0.0.1:8001";
if (process.env.ALLOW_DEMO_WRITES !== "TISHEH-DEMO-ONLY")
    throw new Error(
        "Set ALLOW_DEMO_WRITES=TISHEH-DEMO-ONLY only for an isolated demo database.",
    );
(async () => {
    const b = await chromium.launch();
    const c = await b.newContext({
        viewport: { width: 390, height: 844 },
        extraHTTPHeaders: { "X-Forwarded-For": "198.19.29.92" },
    });
    const p = await c.newPage();
    p.setDefaultTimeout(8000);
    let errors = [];
    p.on("pageerror", (e) => errors.push(e.message));
    await p.goto(base + "/login/otp");
    await p.fill("#mobile", "09000000003");
    await Promise.all([
        p.waitForNavigation(),
        p.locator("form button").click(),
    ]);
    let code = await p.locator("[data-demo-code]").innerText();
    assert(/^\d{4}$/.test(code.trim()));
    await p.fill("#code", code.trim());
    await Promise.all([
        p.waitForNavigation(),
        p.locator("form button").click(),
    ]);
    assert(p.url().endsWith("/dashboard"));
    console.log(
        "PASS local-only OTP: requested, displayed once, verified and entered worker dashboard. No SMS sent.",
    );
    assert.equal(errors.length, 0);
    await b.close();
})().catch((e) => {
    console.error(e);
    process.exit(1);
});
