"use strict";
// Progressive enhancement only; all routes and forms still work without JavaScript.
document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        const revealed = input.type === "password";
        input.type = revealed ? "text" : "password";
        button.setAttribute("aria-pressed", String(revealed));
        button.setAttribute(
            "aria-label",
            revealed ? "پنهان کردن رمز عبور" : "نمایش رمز عبور",
        );
    });
});
document
    .querySelectorAll("[data-registration-wizard],[data-multistep-form]")
    .forEach((wizard) => {
        const steps = [...wizard.querySelectorAll("[data-onboarding-step]")];
        const next = wizard.querySelector("[data-step-next]");
        const back = wizard.querySelector("[data-step-back]");
        const submits = [
            ...wizard.querySelectorAll(
                "[data-register-submit],[data-final-submit]",
            ),
        ];
        const indicators = [
            ...wizard.parentElement.querySelectorAll("[data-step-indicator]"),
        ];
        let index = 0;
        const fields = (section) =>
            [...section.querySelectorAll("input,select,textarea")].filter(
                (input) => !input.disabled,
            );
        function render(focus) {
            steps.forEach((step, i) => {
                step.hidden = i !== index;
            });
            indicators.forEach((item, i) => {
                if (i === index) item.setAttribute("aria-current", "step");
                else item.removeAttribute("aria-current");
            });
            back.hidden = index === 0;
            next.hidden = index === steps.length - 1;
            submits.forEach((button) => {
                button.hidden = index !== steps.length - 1;
            });
            if (focus) {
                const heading = steps[index].querySelector("h2");
                heading.tabIndex = -1;
                heading.focus({ preventScroll: true });
                if (window.matchMedia("(max-width:767px)").matches)
                    wizard.scrollIntoView({ behavior: "auto", block: "start" });
            }
        }
        function validate(section) {
            const invalid = fields(section).find(
                (input) => !input.checkValidity(),
            );
            if (invalid) {
                invalid.reportValidity();
                return false;
            }
            return true;
        }
        next.addEventListener("click", () => {
            if (validate(steps[index])) {
                index = Math.min(index + 1, steps.length - 1);
                render(true);
            }
        });
        back.addEventListener("click", () => {
            index = Math.max(index - 1, 0);
            render(true);
        });
        wizard.addEventListener("submit", (event) => {
            if (index < steps.length - 1) {
                event.preventDefault();
                if (validate(steps[index])) {
                    index++;
                    render(true);
                }
                return;
            }
            // Validate all enabled fields, first revealing the relevant step for focus.
            for (let i = 0; i < steps.length; i++) {
                const invalid = fields(steps[i]).find(
                    (input) => !input.checkValidity(),
                );
                if (invalid) {
                    event.preventDefault();
                    index = i;
                    render(true);
                    invalid.reportValidity();
                    return;
                }
            }
        });
        wizard.querySelectorAll("details").forEach((detail) => {
            if (detail.querySelector('.error,[aria-invalid="true"]'))
                detail.open = true;
        });
        const errorStep = steps.findIndex((step) =>
            step.querySelector('.error,[aria-invalid="true"]'),
        );
        if (errorStep >= 0) index = errorStep;
        wizard.noValidate = true;
        wizard.classList.add("onboarding-enhanced");
        render(false);
    });
document.querySelectorAll("[data-filter-toggle]").forEach((button) => {
    const form = document.getElementById(button.getAttribute("aria-controls"));
    if (!form) return;
    // Keep submitted filters visible. The native form remains available without JS.
    const hasFilters = [...new URLSearchParams(location.search).values()].some(
        (value) => value !== "",
    );
    if (
        window.matchMedia("(max-width:767px)").matches &&
        !hasFilters &&
        !location.hash
    )
        form.hidden = true;
    button.setAttribute("aria-expanded", String(!form.hidden));
    button.addEventListener("click", () => {
        form.hidden = !form.hidden;
        button.setAttribute("aria-expanded", String(!form.hidden));
        if (!form.hidden) form.querySelector("input,select")?.focus();
    });
});
// Native details drawer: close it on Escape or when tapping outside, retaining focus.
document.querySelectorAll(".app-menu").forEach((menu) => {
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && menu.open) {
            menu.open = false;
            menu.querySelector("summary").focus();
        }
    });
    document.addEventListener("click", (event) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
