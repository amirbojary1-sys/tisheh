const { chromium } = require("playwright");
const fs = require("fs");
const assert = require("assert");
const crypto = require("crypto");
const base = process.env.BASE_URL || "http://127.0.0.1:8001";
if (process.env.ALLOW_DEMO_WRITES !== "TISHEH-DEMO-ONLY")
    throw new Error(
        "This test writes demo records. Set ALLOW_DEMO_WRITES=TISHEH-DEMO-ONLY only against an isolated demo database.",
    );
if (!process.env.DEMO_CREDENTIALS_FILE)
    throw new Error(
        "Set DEMO_CREDENTIALS_FILE to a private DemoSeeder output file. Never commit this file.",
    );
const screenshotDir =
    process.env.QA_SCREENSHOTS ||
    require("path").join(__dirname, "screenshots");
fs.mkdirSync(screenshotDir, { recursive: true });
const creds = Object.fromEntries(
    fs
        .readFileSync(process.env.DEMO_CREDENTIALS_FILE, "utf8")
        .split("\n")
        .filter((l) => l.includes(" | "))
        .map((l) => {
            let [role, mobile, password] = l.split(" | ");
            return [role, { mobile, password }];
        }),
);
(async () => {
    const browser = await chromium.launch({ headless: true });
    const errors = [];
    let pagesChecked = 0;
    async function setup(width = 390) {
        const context = await browser.newContext({
            viewport: { width, height: 844 },
            extraHTTPHeaders: {
                "X-Forwarded-For": `198.18.${crypto.randomInt(0, 255)}.${crypto.randomInt(1, 255)}`,
            },
        });
        const p = await context.newPage();
        p.on("pageerror", (e) => errors.push(e.message));
        p.on("console", (m) => {
            if (m.type() === "error") errors.push(m.text());
        });
        return p;
    }
    async function go(p, path) {
        const r = await p.goto(base + path);
        assert(r.status() < 400, `${path}: ${r.status()}`);
        await p.waitForTimeout(60);
        pagesChecked++;
        const d = await p.evaluate(() => ({
            scroll: document.documentElement.scrollWidth,
            width: innerWidth,
            broken: [...document.images]
                .filter((i) => i.complete && !i.naturalWidth)
                .map((i) => i.src),
        }));
        assert(d.scroll <= d.width, `Overflow ${path} ${d.scroll}/${d.width}`);
        assert.equal(d.broken.length, 0, `Broken image ${path}`);
    }
    async function clickSubmit(p, selector) {
        await Promise.all([p.waitForNavigation(), p.locator(selector).click()]);
    }
    async function login(role, width = 390) {
        const p = await setup(width);
        await go(p, "/login");
        await p.fill("#mobile", creds[role].mobile);
        await p.fill("#password", creds[role].password);
        await clickSubmit(p, 'form[action$="/login"] button[type=submit]');
        assert(!p.url().endsWith("/login"), `login ${role}`);
        return p;
    }
    // Registration is genuinely persisted by the server for each supported role.
    for (const [i, role] of ["worker", "employer", "seller"].entries()) {
        const p = await setup();
        await go(p, "/register");
        await p.locator(".craft-role-picker label").nth(i).click();
        await p.locator("[data-step-next]").click();
        assert.equal(
            await p.locator('[data-onboarding-step="1"]').isVisible(),
            true,
        );
        await p.fill("#name", "آزمون");
        await p.fill("#last_name", "طراحی " + role);
        await p.fill(
            "#mobile",
            "0997" + String(crypto.randomInt(0, 10000000)).padStart(7, "0"),
        );
        await p.fill("#city", "تهران");
        let pass = "Qa1zZ" + crypto.randomBytes(12).toString("base64url");
        await p.fill("#password", pass);
        await p.fill("#password_confirmation", pass);
        await p.locator("[data-step-back]").click();
        assert(await p.locator(`input[name="role"][value="${role}"]`).isChecked());
        await p.locator("[data-step-next]").click();
        assert.equal(await p.inputValue("#name"), "آزمون");
        await p.locator("[data-step-next]").click();
        assert(await p.locator('[data-onboarding-step="2"]').isVisible());
        if (role === "worker") await p.fill("#specialty", "برق‌کاری آزمایشی");
        if (role === "seller") {
            await p.fill("#business_name", "فروشگاه آزمایش طراحی");
            await p.fill("#address", "نشانی عمومی آزمایشی، معامله واقعی نیست");
        }
        await p.check("input[name=terms]");
        await clickSubmit(p, "[data-register-submit]");
        assert(
            p.url().endsWith("/dashboard"),
            `registration ${role}: ${p.url()}`,
        );
        console.log(
            "PASS registration wizard, back/next and persisted " + role,
        );
        await p.context().close();
    }
    // Server-side validation restores the role and reveals the step containing the error.
    {
        const p = await setup();
        await go(p, "/register");
        await p.locator("[data-step-next]").click();
        await p.fill("#name", "خطای");
        await p.fill("#last_name", "آزمایش");
        await p.fill("#mobile", creds.worker.mobile);
        await p.fill("#city", "تهران");
        await p.fill("#password", "SecureDemo2026");
        await p.fill("#password_confirmation", "SecureDemo2026");
        await p.locator("[data-step-next]").click();
        await p.fill("#specialty", "بنایی");
        await p.check("[name=terms]");
        await clickSubmit(p, "[data-register-submit]");
        assert(p.url().endsWith("/register"));
        assert(await p.locator('[data-onboarding-step="1"]').isVisible());
        assert(await p.locator("#mobile[aria-invalid=true]").isVisible());
        console.log("PASS validation/error step restoration");
        await p.context().close();
    }
    const worker = await login("worker"),
        employer = await login("employer"),
        seller = await login("seller"),
        admin = await login("admin", 1440);
    for (const [role, p] of [
        ["worker", worker],
        ["employer", employer],
        ["seller", seller],
        ["admin", admin],
    ]) {
        const paths = [
            "/dashboard",
            "/notifications",
            "/messages",
            "/reports",
            "/reports/create",
            "/orders",
            "/cart",
            "/jobs",
            "/materials",
            "/materials/1",
            "/compare?ids[]=1&ids[]=5",
            "/terms",
            "/pages/help",
        ];
        if (role === "employer") paths.push("/jobs/create", "/jobs/1/edit");
        if (role === "seller")
            paths.push("/materials/create", "/materials/1/edit");
        if (role === "admin")
            paths.push(
                "/admin",
                "/admin/users",
                "/admin/users/1",
                "/admin/listings/job",
                "/admin/listings/product",
                "/admin/categories",
                "/admin/reports",
                "/admin/settings",
                "/admin/pages",
                "/admin/orders",
                "/admin/roles",
                "/admin/finance",
                "/admin/demo-payments",
                "/admin/audit",
                "/admin/system",
            );
        for (const path of paths) await go(p, path);
        await go(p, role === "admin" ? "/admin" : "/dashboard");
        await p.screenshot({
            path: `${screenshotDir}/craft-${role}-dashboard.png`,
            fullPage: true,
        });
        console.log("PASS " + role + " route and responsive checks");
    }
    // Job wizard creates a draft, edits it, submits for review and publishes via the real admin form.
    await go(employer, "/jobs/create");
    await employer.locator(".job-category-picker label").first().click();
    await employer.fill("#title", "درخواست آزمایشی طراحی فرم");
    await employer.fill("#skills", "تخصص نمونه برای آزمون رابط");
    await employer.locator("[data-step-next]").click();
    await employer.fill("#city", "تهران");
    await employer.fill(
        "#address",
        "نشانی خصوصی آزمایش قالب؛ پروژه واقعی نیست.",
    );
    await employer.screenshot({
        path: `${screenshotDir}/craft-job-location.png`,
        fullPage: true,
    });
    await employer.locator("[data-step-next]").click();
    await employer.fill("#workers_count", "2");
    await employer.fill(
        "#description",
        "این درخواست برای آزمایش واقعی فرم مرحله‌ای طراحی ثبت شده و پیشنهاد کار واقعی نیست.",
    );
    await clickSubmit(employer, "button[data-final-submit][value=draft]");
    const newJobPath = new URL(employer.url()).pathname;
    assert(/^\/jobs\/\d+$/.test(newJobPath), newJobPath);
    assert(
        await employer.locator(".tag").filter({ hasText: "پیش‌نویس" }).count(),
    );
    await go(employer, newJobPath + "/edit");
    await employer.locator("[data-step-next]").click();
    assert.equal(
        await employer.inputValue("#address"),
        "نشانی خصوصی آزمایش قالب؛ پروژه واقعی نیست.",
    );
    await employer.locator("[data-step-next]").click();
    await clickSubmit(employer, "button[data-final-submit][value=pending]");
    await go(admin, "/admin/listings/job?status=pending");
    const review = admin
        .locator(".review-row")
        .filter({ has: admin.locator(`a[href$="${newJobPath}"]`) });
    assert(await review.count());
    await Promise.all([
        admin.waitForNavigation(),
        review.locator("button[value=published]").click(),
    ]);
    await go(worker, newJobPath);
    assert.equal(await worker.locator('form[action$="/apply"]').count(), 1);
    console.log("PASS job wizard draft/create/edit/review/publication");
    // Worker applies; employer sees the saved application, accepts it, worker gets a real notice.
    await go(worker, newJobPath);
    let application = worker.locator('form[action$="/apply"]');
    if (await application.count()) {
        await application
            .locator("textarea")
            .fill("این درخواست کاملاً آزمایشی برای بررسی قالب و گردش کار است.");
        await Promise.all([
            worker.waitForNavigation(),
            application.locator("button").click(),
        ]);
        assert(await worker.locator("[role=status]").count());
    }
    await go(employer, newJobPath);
    let accept = employer
        .locator('form[action*="/applications/"] button[value=accepted]')
        .first();
    assert(await accept.count(), "worker application persisted");
    await Promise.all([employer.waitForNavigation(), accept.click()]);
    await go(worker, "/notifications");
    assert(await worker.locator(".notice-card").count());
    await worker.screenshot({
        path: `${screenshotDir}/craft-notifications.png`,
        fullPage: true,
    });
    await clickSubmit(worker, 'form[action$="read-all"] button');
    console.log("PASS apply, employer acceptance and notifications/read-all");
    // Messaging starts a real thread and persists a message.
    await go(worker, "/jobs/1");
    await clickSubmit(worker, 'form[action*="/messages/start/"] button');
    await worker.fill(
        "#body",
        "پیام آزمایشی طراحی؛ هیچ توافق یا معامله واقعی نیست.",
    );
    await clickSubmit(worker, ".chat-compose button");
    assert((await worker.locator(".chat-bubble").count()) > 0);
    await worker.screenshot({
        path: `${screenshotDir}/craft-chat.png`,
        fullPage: true,
    });
    console.log("PASS messaging round trip");
    // Cart quantity update and checkout reach real persisted orders, without payment.
    await go(worker, "/materials/1");
    const add = worker.locator('form[action*="/cart/items/"]').first();
    await add.locator("[name=quantity]").fill("10");
    await Promise.all([
        worker.waitForNavigation(),
        add.locator("button").click(),
    ]);
    await go(worker, "/cart");
    await worker.fill("#quantity-1", "11");
    await clickSubmit(
        worker,
        'form[action$="/cart/items/1"]:has(input[value=patch]) button',
    );
    await worker.screenshot({
        path: `${screenshotDir}/craft-cart.png`,
        fullPage: true,
    });
    await worker.fill(
        "#delivery_address",
        "آدرس آزمایشی برای بررسی قالب؛ ارسال واقعی انجام نشود.",
    );
    await clickSubmit(worker, 'form[action$="/checkout"] button');
    assert(!worker.url().endsWith("/cart"), worker.url());
    await go(worker, "/orders");
    let link = worker.locator('a[href*="/orders/"]').first();
    assert(await link.count());
    await go(worker, new URL(await link.getAttribute("href"), base).pathname);
    await worker.screenshot({
        path: `${screenshotDir}/craft-order.png`,
        fullPage: true,
    });
    console.log(
        "PASS cart add/update/checkout and order detail; no bank payment",
    );
    // Filter, menu, password toggle and desktop/tablet/mobile layout.
    await go(worker, "/jobs");
    assert(await worker.locator("#job-filters").isHidden());
    await worker.locator("[data-filter-toggle]").click();
    await worker.fill("#q", "برق");
    await clickSubmit(worker, "#job-filters button[type=submit]");
    assert(await worker.locator("#job-filters").isVisible());
    assert.equal(await worker.locator(".craft-job-card").count(), 1);
    await worker.locator(".app-menu summary").click();
    assert((await worker.locator(".app-menu").getAttribute("open")) !== null);
    await worker.keyboard.press("Escape");
    assert((await worker.locator(".app-menu").getAttribute("open")) === null);
    console.log("PASS real search/filter and keyboard drawer");
    for (const width of [320, 390, 768, 1440]) {
        await admin.setViewportSize({ width, height: 844 });
        for (const path of [
            "/admin",
            "/admin/users",
            "/admin/categories",
            "/admin/settings",
            "/dashboard",
            "/notifications",
            "/cart",
        ])
            await go(admin, path);
    }
    console.log("PASS administrator responsive routes at 320/390/768/1440");
    const guest = await setup();
    await go(guest, "/login");
    await guest.fill("#password", "NotASavedPassword");
    await guest.locator("[data-password-toggle]").click();
    assert.equal(await guest.getAttribute("#password", "type"), "text");
    await guest.locator("[data-password-toggle]").click();
    assert.equal(await guest.getAttribute("#password", "type"), "password");
    // No-JS fallback remains a real form (all onboarding sections visible).
    const nojs = await browser.newContext({
        javaScriptEnabled: false,
        viewport: { width: 390, height: 844 },
    });
    const np = await nojs.newPage();
    await go(np, "/register");
    assert.equal(await np.locator("[data-onboarding-step]:visible").count(), 3);
    await nojs.close();
    console.log("CHECKED", pagesChecked, "page visits");
    console.log("BROWSER_ERRORS", JSON.stringify([...new Set(errors)]));
    assert.equal(errors.length, 0);
    await browser.close();
    console.log("ALL CHECKS PASSED");
})().catch((e) => {
    console.error(e.stack);
    process.exit(1);
});
