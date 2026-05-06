const { test, expect } = require('@playwright/test');

test('Flujo completo: Login, Catálogo y Carrito', async ({ page }) => {
    // 1. Login
    await page.goto('http://localhost/librerias_jok/pages/login.php');
    await page.fill('input[name="email"]', 'jok@gmail.com');
    await page.fill('input[name="password"]', '200208');
    await page.click('button:has-text("Iniciar sesión")');

    // 2. Validar llegada al Catálogo
    await page.waitForURL('**/catalogo.php');
    // Usamos una expresión regular más específica para el título
    await expect(page).toHaveTitle(/Catálogo/i);
    
    // Verificamos que el encabezado "Nuestro Catálogo" sea visible
    const tituloSeccion = page.locator('h1:has-text("Nuestro Catálogo")');
    await expect(tituloSeccion).toBeVisible();

    // 3. Simular clic en el icono del carrito (para ver que está vacío o navegar)
    // Opcional: Si quieres probar el botón del carrito arriba a la derecha:
    await page.click('a[href*="carrito.php"]');

    // 4. Validar llegada a Mi Carrito
    await page.waitForURL('**/carrito.php');
    await expect(page).toHaveTitle(/Mi Carrito/i);

    // 5. Verificar que el carrito está vacío (según tu segunda imagen)
    const mensajeVacio = page.locator('text=Tu carrito está vacío');
    await expect(mensajeVacio).toBeVisible();

    // 6. Regresar al catálogo usando el botón amarillo de tu imagen
    await page.click('text=Ver catálogo');
    await expect(page).toHaveURL(/catalogo.php/);

    console.log("¡Prueba de navegación completa exitosa!");
});