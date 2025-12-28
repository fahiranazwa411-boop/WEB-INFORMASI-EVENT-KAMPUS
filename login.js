// Pastikan feather icons dirender
if (window.feather) {
  feather.replace();
}

function togglePassword() {
  const passwordInput = document.getElementById("password");
  const eyeIcon = document.getElementById("eyeIcon");
  if (!passwordInput || !eyeIcon) return;

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    eyeIcon.setAttribute("data-feather", "eye-off");
  } else {
    passwordInput.type = "password";
    eyeIcon.setAttribute("data-feather", "eye");
  }

  if (window.feather) {
    feather.replace();
  }
}

function showToast(message, type = "success") {
  // type: "success" | "danger" | "warning" | "info"
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.textContent = message;

  // Styling toast sederhana berbasis Bootstrap class + inline positioning
  toast.className =
    "position-fixed top-0 end-0 m-3 px-3 py-2 rounded shadow text-white z-3";

  // Tentukan warna
  const bgClass = {
    success: "bg-success",
    danger: "bg-danger",
    warning: "bg-warning",
    info: "bg-info",
  }[type] || "bg-success";

  toast.classList.add(bgClass);

  // Tampilkan
  toast.style.opacity = "0";
  toast.style.transition = "opacity 0.3s ease";
  toast.classList.remove("hidden");

  setTimeout(() => {
    toast.style.opacity = "1";
  }, 10);

  // Hilangkan
  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => {
      toast.classList.add("hidden");
    }, 300);
  }, 3000);
}

// Baca query string: ?sukses=login_berhasil atau ?error=password_salah, dll.
(function handleQueryToast() {
  const urlParams = new URLSearchParams(window.location.search);

  if (urlParams.has("sukses")) {
    const message =
      {
        login_berhasil: "Login successful! Welcome back.",
      }[urlParams.get("sukses")] || "Success!";
    showToast(message, "success");
  } else if (urlParams.has("error")) {
    const message =
      {
        password_salah: "Incorrect password!",
        akun_tidak_ditemukan: "Account not found!",
      }[urlParams.get("error")] || "An error occurred.";
    showToast(message, "danger");
  }
})();
