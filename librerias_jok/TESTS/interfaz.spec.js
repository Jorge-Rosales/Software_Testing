const { test, expect } = require('@playwright/test');

test('Flujo completo: Login y validación de Catálogo', async ({ page }) => {
    // 1. Ir a la página de login
    await page.goto('http://localhost/librerias_jok/pages/login.php');

    // 2. Rellenar el formulario con las credenciales correctas
    // Asegúrate de que los selectores [name="..."] coincidan con tu HTML
    await page.fill('input[name="email"]', 'jok@gmail.com');
    await page.fill('input[name="password"]', '200208');

    // 3. Hacer clic en el botón de Iniciar sesión
    // Usamos el texto del botón para asegurar el clic
    await page.click('button:has-text("Iniciar sesión")');

    // 4. VALIDACIÓN CRUCIAL: Esperar a que la URL cambie a catalogo.php
    // Esto evita que el test falle si la base de datos tarda un poco en responder
    await page.waitForURL('**/catalogo.php', { timeout: 10000 });

    // 5. Verificar que estamos en la URL correcta
    await expect(page).toHaveURL(/catalogo.php/);

    // 6. Validar que el título de la página sea el esperado
    await expect(page).toHaveTitle(/Librerías JOK/);

    // 7. Interactuar con el catálogo (ejemplo: buscar el primer botón de compra)
    const botonCompra = page.locator('.btn-outline-gold').first();
    await expect(botonCompra).toBeVisible();
    
    console.log("¡Login exitoso y catálogo cargado correctamente!");
});