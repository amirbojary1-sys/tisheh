document.querySelectorAll("[data-global-search]").forEach((form) => {
    const select = form.querySelector("[data-search-type]");
    select.addEventListener("change", () => {
        form.action =
            select.value === "products"
                ? select.dataset.products
                : select.dataset.jobs;
    });
});
const updateRole = () => {
    const role = document.querySelector('input[name="role"]:checked')?.value;
    document.querySelectorAll("[data-role-field]").forEach((el) => {
        const visible = el.dataset.roleField === role;
        el.hidden = !visible;
        el.querySelectorAll("input").forEach((input) => {
            input.required = visible;
            input.disabled = !visible;
        });
    });
};
document
    .querySelectorAll('input[name="role"]')
    .forEach((el) => el.addEventListener("change", updateRole));
updateRole();
const boxes = [...document.querySelectorAll('input[form="compare-form"]')];
boxes.forEach((box) =>
    box.addEventListener("change", () => {
        if (boxes.filter((x) => x.checked).length > 4) {
            box.checked = false;
            alert("حداکثر ۴ محصول قابل مقایسه است.");
        }
        const count = document.querySelector("[data-compare-count]");
        if (count)
            count.textContent = boxes
                .filter((x) => x.checked)
                .length.toLocaleString("fa-IR");
    }),
);
document.querySelector("#compare-form")?.addEventListener("submit", (event) => {
    if (boxes.filter((x) => x.checked).length < 2) {
        event.preventDefault();
        alert("حداقل ۲ محصول را انتخاب کنید.");
    }
});

document.querySelectorAll("form[data-confirm]").forEach((form) =>
    form.addEventListener("submit", (event) => {
        if (!confirm(form.dataset.confirm)) event.preventDefault();
    }),
);
