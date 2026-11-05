const { chromium } = require("playwright");
const assert = require("assert");
const fs = require("fs");
const crypto = require("crypto");
const base = process.env.BASE_URL || "http://127.0.0.1:8001";
if (
    process.env.ALLOW_DEMO_WRITES !== "TISHEH-DEMO-ONLY" ||
    !process.env.DEMO_CREDENTIALS_FILE
)
    throw new Error(
        "Isolated demo only: set ALLOW_DEMO_WRITES=TISHEH-DEMO-ONLY and private DEMO_CREDENTIALS_FILE.",
    );
const path = require("path");
const screenshots =
    process.env.QA_SCREENSHOTS || path.join(__dirname, "screenshots");
fs.mkdirSync(screenshots, { recursive: true });
const creds = Object.fromEntries(
    fs
        .readFileSync(process.env.DEMO_CREDENTIALS_FILE, "utf8")
        .split("\n")
        .filter((l) => l.includes(" | "))
        .map((l) => {
            let [r, mobile, password] = l.split(" | ");
            return [r, { mobile, password }];
        }),
);
(async () => {
    const browser = await chromium.launch();
    const errors = [];
    let visits = 0;
    async function page(width = 390) {
        const c = await browser.newContext({
            viewport: { width, height: 844 },
            extraHTTPHeaders: {
                "X-Forwarded-For": `198.18.${crypto.randomInt(0, 255)}.${crypto.randomInt(1, 255)}`,
            },
        });
        const p = await c.newPage();
        p.setDefaultTimeout(10000);
        p.on("pageerror", (e) => errors.push(e.message));
        p.on("console", (m) => {
            if (m.type() === "error") errors.push(m.text());
        });
        return p;
    }
    async function go(p, path) {
        const r = await p.goto(base + path);
        assert(r.status() < 400, `${path}: ${r.status()}`);
        visits++;
        await p.waitForTimeout(80);
        const d = await p.evaluate(() => ({
            scroll: document.documentElement.scrollWidth,
            width: innerWidth,
            broken: [...document.images]
                .filter((i) => i.complete && !i.naturalWidth)
                .map((i) => i.src),
        }));
        assert(d.scroll <= d.width, `Overflow ${path}: ${JSON.stringify(d)}`);
        assert.equal(d.broken.length, 0, `Broken image ${path}`);
    }
    async function submit(p, selector) {
        await Promise.all([p.waitForNavigation(), p.locator(selector).click()]);
    }
    async function login(role, width = 390) {
        const p = await page(width);
        await go(p, "/login");
        await p.fill("#mobile", creds[role].mobile);
        await p.fill("#password", creds[role].password);
        await submit(p, 'form[action$="/login"] button[type=submit]');
        assert(!p.url().endsWith("/login"));
        return p;
    }
    let p = await page(),
        admin = await login("admin", 1440),
        employer = await login("employer");
    const mobile =
            "0998" + String(crypto.randomInt(0, 10000000)).padStart(7, "0"),
        password = "Network1Z" + crypto.randomBytes(9).toString("hex");
    let first = String(crypto.randomInt(100000000, 999999999)),
        sum = [...first].reduce((s, x, i) => s + Number(x) * (10 - i), 0),
        rem = sum % 11,
        national = first + String(rem < 2 ? rem : 11 - rem);
    await go(p, "/register");
    await p.locator(".craft-role-picker label").nth(1).click();
    await p.check('[name="additional_roles[]"][value=worker]');
    await p.check('[name="additional_roles[]"][value=seller]');
    await p.locator("[data-step-next]").click();
    await p.fill("#name", "متخصص آزمایشی");
    await p.fill("#last_name", "شبکه ساختمان");
    await p.fill("#mobile", mobile);
    await p.fill("#city", "تهران");
    await p.fill("#password", password);
    await p.fill("#password_confirmation", password);
    await p.locator("[data-step-next]").click();
    await p.fill("#specialty", "مهندسی عمران");
    await p.fill("#business_name", "فروشگاه نمونه شبکه");
    await p.fill("#address", "آدرس عمومی آزمایشی فروشگاه");
    await p.locator(".optional-identity summary").click();
    await p.fill("#national_id", national);
    await p.fill("#contact_phone", "02112345678");
    await p.fill("#postal_code", "1234567890");
    await p.fill("#private_address", "آدرس خصوصی آزمایشی شبکه؛ عمومی نشود.");
    await p.check("[name=terms]");
    await submit(p, "[data-register-submit]");
    assert(p.url().endsWith("/dashboard"));
    console.log("PASS multi-role signup and optional private identity");
    for (const path of [
        "/account/new",
        "/account/roles",
        "/account/identity",
        "/jobs/create",
        "/materials/create",
        "/account/professional",
    ])
        await go(p, path);
    await go(p, "/account/identity");
    assert(!(await p.content()).includes(national));
    await submit(p, 'form[action$="/identity/submit"] button');
    assert(
        (await p.locator(".panel h2").first().innerText()).includes("انتظار"),
    );
    await go(admin, "/admin/identities");
    let identityLink = admin
        .locator("a.list-row")
        .filter({ hasText: "متخصص آزمایشی" })
        .first();
    assert(await identityLink.count());
    let identityPath = new URL(await identityLink.getAttribute("href"))
        .pathname;
    let id = identityPath.split("/").pop();
    await go(admin, identityPath);
    assert((await admin.content()).includes(national));
    await admin.fill(
        "#note",
        "بررسی اداری داده آزمایشی؛ هیچ استعلام رسمی انجام نشده است.",
    );
    await submit(admin, "button[value=reviewed]");
    await go(p, "/account/identity");
    assert((await p.content()).includes("بررسی اداری انجام شده"));
    await p.fill("#current_password", password);
    await p.fill(
        "#private_address",
        "نشانی خصوصی تازه برای بررسی ابطال نتیجه اداری",
    );
    await submit(p, 'form[action$="/account/identity"] button');
    assert(
        (await p.locator(".panel h2").first().innerText()).includes(
            "ارسال نشده",
        ),
    );
    console.log(
        "PASS private review, audit and invalidation after identity edit",
    );
    await go(p, "/account/professional");
    await p.fill("#headline", "مهندس عمران آزمایشی شبکه");
    await p.fill("#specialty", "اجرای سازه آزمایشی");
    await p.fill(
        "#bio",
        "این پروفایل و نمونه‌کار صرفاً داده آزمایشی برای آزمون سامانه تیشه است و خدمات واقعی ارائه نمی‌کند.",
    );
    await p.selectOption("#activity_type", "engineer");
    await p.fill("#experience_years", "5");
    await p
        .locator(".skill-picker label")
        .filter({ hasText: "مهندسی عمران" })
        .locator("input")
        .check();
    await p.check("[name=directory_enabled][type=checkbox]");
    await submit(p, 'form[action$="/account/professional"] button');
    await p.fill("#title", "نمونه‌کار آزمایشی سازه");
    await p.fill(
        "#description",
        "شرح آزمایشی نمونه‌کار برای تست گردش انتشار؛ پروژه واقعی نیست.",
    );
    await p.setInputFiles(
        "#image",
        path.join(__dirname, "../../public/images/materials/cement.webp"),
    );
    await submit(p, 'form[action$="/account/portfolio"] button');
    await p.locator(".portfolio-grid details summary").first().click();
    let edit = p.locator('form[action*="/account/portfolio/"]').first();
    await edit.locator("[name=title]").fill("نمونه‌کار ویرایش‌شده آزمایشی");
    await edit
        .locator("[name=description]")
        .fill("شرح اصلاح‌شده و آزمایشی نمونه‌کار؛ پروژه واقعی نیست.");
    await Promise.all([p.waitForNavigation(), edit.locator("button").click()]);
    console.log("PASS portfolio metadata edit");
    await go(admin, "/admin/professionals");
    let card = admin
        .locator("article.panel")
        .filter({ hasText: "مهندس عمران آزمایشی شبکه" })
        .first();
    assert(await card.count());
    await card
        .locator("textarea[name=note]")
        .fill("محتوای آزمایشی برای نمایش پروفایل بررسی شد.");
    await Promise.all([
        admin.waitForNavigation(),
        card.locator("button[value=published]").click(),
    ]);
    let publicPath = "/professionals/" + id;
    await go(employer, publicPath);
    assert(!(await employer.content()).includes(national));
    assert(!(await employer.content()).includes(mobile));
    assert(!(await employer.content()).includes("02112345678"));
    await submit(
        employer,
        'form[action*="/messages/start/professional/"] button',
    );
    await employer.fill(
        "#body",
        "پیام آزمایشی ارتباط کارفرما با متخصص ساختمان",
    );
    await submit(employer, ".chat-compose button");
    assert(await employer.locator(".chat-bubble").count());
    console.log(
        "PASS moderated professional profile, sanitized private portfolio and employer chat",
    );
    await go(
        employer,
        "/professionals?activity=engineer&city=" + encodeURIComponent("تهران"),
    );
    assert(await employer.locator(".professional-card").count());
    await go(employer, publicPath);
    await employer.locator('a[href*="/reports/create"]').click();
    await employer.fill("#reason", "گزارش پروفایل آزمایشی");
    await employer.fill(
        "#description",
        "این گزارش صرفاً برای بررسی چرخه رسیدگی و پاسخ مدیر ثبت شده است.",
    );
    await submit(employer, 'form[action$="/reports"] button');
    await go(admin, "/admin/reports");
    let report = admin
        .locator("a")
        .filter({ hasText: "گزارش پروفایل آزمایشی" })
        .first();
    assert(await report.count());
    await report.click();
    await admin.selectOption("#status", "resolved");
    await admin.fill(
        "#admin_response",
        "پاسخ آزمایشی مدیر برای بررسی پشتیبانی ثبت شد.",
    );
    await submit(admin, 'form[action*="/admin/reports/"] button');
    await go(employer, "/reports");
    assert((await employer.content()).includes("پاسخ آزمایشی مدیر"));
    console.log("PASS professional report and admin response");
    for (const width of [320, 390, 768, 1440]) {
        await p.setViewportSize({ width, height: 844 });
        await admin.setViewportSize({ width, height: 844 });
        for (const path of [
            "/professionals",
            publicPath,
            "/account/identity",
            "/account/roles",
            "/account/professional",
            "/account/new",
        ])
            await go(p, path);
        for (const path of [
            "/admin/professionals",
            "/admin/identities",
            identityPath,
        ])
            await go(admin, path);
    }
    await p.screenshot({
        path: path.join(screenshots, "network-account.png"),
        fullPage: true,
    });
    console.log("PASS new-page responsive checks at 320/390/768/1440");
    await go(p, "/account/professional");
    await p.uncheck("[name=directory_enabled][type=checkbox]");
    await submit(p, 'form[action$="/account/professional"] button');
    let guest = await page();
    let r = await guest.request.get(base + publicPath);
    assert.equal(r.status(), 404);
    console.log("PASS opt-out immediately hides public profile");
    console.log(
        "VISITS",
        visits,
        "BROWSER_ERRORS",
        JSON.stringify([...new Set(errors)]),
    );
    assert.equal(errors.length, 0);
    await browser.close();
    console.log("ALL NETWORK CHECKS PASSED");
})().catch((e) => {
    console.error(e.stack);
    process.exit(1);
});
