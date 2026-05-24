// js/db.js
// Put this file inside: C:\xampp\htdocs\project\js\db.js

const API_URL = window.FURRYNEEDS_API_URL || "api/product-detail.php";

document.addEventListener("DOMContentLoaded", function () {
    updateCartCount();
});

window.addToCart = async function (productId, quantity = 1) {
    productId = parseInt(productId, 10);
    quantity = parseInt(quantity, 10) || 1;

    if (!productId || productId <= 0) {
        showToast("❌ Invalid product ID.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append("product_id", productId);
        formData.append("quantity", quantity);

        const response = await fetch(`${API_URL}?action=add_to_cart`, {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            setCartBadge(data.cart_count);
            showToast("🛒 Added to cart!");
        } else {
            showToast("❌ " + (data.message || "Add to cart failed."));
        }
    } catch (error) {
        console.error("Add to cart error:", error);
        showToast("❌ Cannot connect to database/API.");
    }
};

async function updateCartCount() {
    try {
        const response = await fetch(`${API_URL}?action=cart_count`);
        const data = await response.json();

        if (data.success) {
            setCartBadge(data.cart_count);
        }
    } catch (error) {
        console.error("Cart count error:", error);
    }
}

function setCartBadge(count) {
    const badge = document.getElementById("cartBadge") || document.querySelector(".icon-btn .badge");
    if (badge) badge.textContent = count;
}

function showToast(message) {
    let toast = document.getElementById("toast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "toast";
        toast.textContent = message;
        toast.style.position = "fixed";
        toast.style.bottom = "30px";
        toast.style.right = "30px";
        toast.style.background = "#333";
        toast.style.color = "white";
        toast.style.padding = "14px 24px";
        toast.style.borderRadius = "8px";
        toast.style.zIndex = "9999";
        toast.style.fontWeight = "600";
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
        return;
    }

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2500);
}
