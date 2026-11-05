const { chromium } = require("playwright");
const fs = require("fs");
const assert = require("assert");
if (
    process.env.TISHEH_E2E_ALLOW_WRITE !== "1" ||
    !process.env.TISHEH_CREDENTIALS
)
    throw new Error(
        "Disposable DemoSeeder database only: set TISHEH_E2E_ALLOW_WRITE=1 and TISHEH_CREDENTIALS",
    );
(async () => {
    const b = await chromium.launch({ args: ["--no-sandbox"] });
    const base = process.env.TISHEH_BASE_URL || "http://127.0.0.1:8000";
    const creds = Object.fromEntries(
        fs
            .readFileSync(process.env.TISHEH_CREDENTIALS, "utf8")
            .split("\n")
            .filter((l) => l.includes(" | "))
            .map((l) => {
                const [r, m, p] = l.split(" | ");
                return [r, { m, p }];
            }),
    );
    const logs = [];
    let errors = [];
    async function page(role) {
        const c = await b.newContext({
            viewport: { width: 1440, height: 1000 },
        });
        const p = await c.newPage();
        p.on("pageerror", (e) => errors.push(e.message));
        if (role) {
            await p.goto(base + "/login");
            await p.locator("[name=mobile]").fill(creds[role].m);
            await p.locator("[name=password]").fill(creds[role].p);
            await p.locator("button[type=submit]").click();
            await p.waitForURL("**/dashboard");
        }
        return p;
    }
    const admin = await page("admin");
    const buyer = await page("employer");
    const seller = await page("seller");
    const worker = await page("worker");
    const adminPaths = [
        "/admin",
        "/admin/users",
        "/admin/users/1",
        "/admin/listings/job",
        "/admin/listings/product",
        "/admin/categories",
        "/admin/reports",
        "/admin/pages",
        "/admin/settings",
        "/admin/audit",
        "/admin/orders",
        "/admin/roles",
        "/admin/finance",
    ];
    for (const path of adminPaths) {
        const res = await admin.goto(base + path);
        assert(res.status() === 200, path + " " + res.status());
    }
    logs.push("13 admin routes: HTTP 200");
    for (const width of [390, 768, 1440]) {
        await admin.setViewportSize({ width, height: 900 });
        for (const path of adminPaths) {
            await admin.goto(base + path);
            assert(
                await admin.evaluate(
                    () => document.documentElement.scrollWidth <= innerWidth,
                ),
                path + " overflow at " + width,
            );
        }
    }
    logs.push(
        "All 13 admin sections: no horizontal document overflow at 390 / 768 / 1440",
    );
    const pub = await page();
    for (const width of [390, 768, 1440]) {
        await pub.setViewportSize({ width, height: 900 });
        for (const path of [
            "/",
            "/jobs",
            "/materials",
            "/pages/help",
            "/register",
            "/materials/1",
        ]) {
            const res = await pub.goto(base + path);
            assert(res.status() === 200, path);
            assert(
                await pub.evaluate(
                    () => document.documentElement.scrollWidth <= innerWidth,
                ),
                path + " overflow at " + width,
            );
            const broken = await pub
                .locator("img")
                .evaluateAll((imgs) =>
                    imgs
                        .filter((x) => x.complete && x.naturalWidth === 0)
                        .map((x) => x.src),
                );
            assert(broken.length === 0, "broken images " + broken);
        }
    }
    logs.push(
        "Public pages: responsive and no broken images at 390 / 768 / 1440",
    );
    // Settings submit: optional blank email must work on real MySQL driver.
    await admin.goto(base + "/admin/settings");
    await admin.locator("[name=support_email]").fill("");
    await admin.locator("[name=publication_fee_rial]").fill("0");
    await admin.locator("[name=subscription_fee_rial]").fill("0");
    await admin.locator("main form button").click();
    await admin.waitForLoadState("networkidle");
    assert(
        (await admin.locator("main").innerText()).includes("تنظیمات ذخیره شد"),
    );
    logs.push("Settings saved with empty optional fields");
    // Genuine conversation, no server-bypassing API mutation.
    await buyer.goto(base + "/materials/1");
    await buyer.locator('form[action*="/messages/start/"] button').click();
    await buyer.waitForURL("**/messages/*");
    const threadURL = buyer.url();
    const suffix = Date.now().toString();
    await buyer
        .locator("[name=body]")
        .fill(
            "آزمون مرورگر: سلام، موجودی این محصول را تأیید می‌کنید؟ " + suffix,
        );
    await buyer.locator(".chat-compose button").click();
    await buyer.waitForLoadState("networkidle");
    await seller.goto(threadURL);
    assert((await seller.locator(".chat-window").innerText()).includes(suffix));
    await seller
        .locator("[name=body]")
        .fill("آزمون مرورگر: بله، پیام شما دریافت شد. " + suffix);
    await seller.locator(".chat-compose button").click();
    await seller.waitForLoadState("networkidle");
    await buyer.goto(threadURL);
    assert(
        (await buyer.locator(".chat-window").innerText()).includes(
            "بله، پیام شما دریافت شد.",
        ),
    );
    logs.push("Two-party messaging and reply persisted");
    // An unrelated account cannot read a thread.
    const denied = await worker.goto(threadURL);
    assert(denied.status() === 403);
    logs.push("Unrelated thread access: blocked (403)");
    // Direct order: amount comes from server, no payment success.
    await buyer.goto(base + "/materials/1");
    await buyer.locator("[name=quantity]").fill("10");
    await buyer
        .locator("[name=delivery_address]")
        .fill("آزمون مرورگر: نشانی خصوصی تحویل مصالح");
    await buyer.locator('form[action$="/order"] button').click();
    await buyer.waitForURL("**/orders/*");
    const orderURL = buyer.url();
    assert((await buyer.locator("main").innerText()).includes("تأییدنشده"));
    await seller.goto(orderURL);
    await seller.locator("button[value=confirmed]").click();
    await seller.waitForLoadState("networkidle");
    await seller.locator("button[value=ready_to_ship]").click();
    await seller.waitForLoadState("networkidle");
    await seller.locator("button[value=shipped]").click();
    await seller.waitForLoadState("networkidle");
    await buyer.goto(orderURL);
    await buyer.locator("button[value=delivered]").click();
    await buyer.waitForLoadState("networkidle");
    assert((await buyer.locator("main").innerText()).includes("تحویل‌شده"));
    logs.push(
        "Direct order → confirmation → ready → shipped → buyer-delivered; payment remains unverified",
    );
    // Support report and admin reply with actual notification.
    await worker.goto(base + "/reports/create");
    const title = "آزمون مرورگر: پشتیبانی " + suffix;
    await worker.locator("[name=reason]").fill(title);
    await worker
        .locator("textarea[name=description]")
        .fill(
            "آزمون واقعی ثبت گزارش و دریافت پاسخ در حساب کاربر، با امکان پیگیری.",
        );
    await worker.locator("main form button").click();
    await worker.waitForURL("**/reports");
    await admin.goto(base + "/admin/reports");
    await admin.getByRole("link").filter({ hasText: title }).click();
    await admin.locator("[name=status]").selectOption("resolved");
    await admin
        .locator("[name=admin_response]")
        .fill("آزمون مرورگر: درخواست شما بررسی و پاسخ داده شد.");
    await admin.locator("main form button").click();
    await admin.waitForLoadState("networkidle");
    await worker.goto(base + "/reports");
    assert(
        (await worker.locator("main").innerText()).includes(
            "درخواست شما بررسی و پاسخ داده شد",
        ),
    );
    await worker.goto(base + "/notifications");
    assert(
        (await worker.locator("main").innerText()).includes("پاسخ به گزارش"),
    );
    logs.push(
        "Support request → admin response → user tracking and database notification",
    );
    // All private app sections also fit on mobile.
    for (const p of [buyer, seller, worker]) {
        await p.setViewportSize({ width: 390, height: 844 });
        for (const path of [
            "/dashboard",
            "/messages",
            "/orders",
            "/notifications",
            "/reports",
        ]) {
            await p.goto(base + path);
            assert(
                await p.evaluate(
                    () => document.documentElement.scrollWidth <= innerWidth,
                ),
                path + " overflow",
            );
        }
    }
    logs.push("Private mobile panels: no horizontal document overflow");
    assert.deepStrictEqual(errors, []);
    logs.push("JavaScript exceptions: 0");
    console.log(logs.join("\n"));
    fs.writeFileSync(
        process.env.TISHEH_BROWSER_REPORT || "storage/logs/browser-results.txt",
        logs.join("\n") + "\n",
    );
    await b.close();
})();
