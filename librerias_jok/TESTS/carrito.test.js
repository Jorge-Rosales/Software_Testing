// Funciones a probar (Lógica de Librerías JOK)
const calcularSubtotal = (precio, cantidad) => precio * cantidad;
const calcularIVA = (subtotal) => subtotal * 0.16;
const validarStock = (cantidadDeseada, stockDisponible) => cantidadDeseada <= stockDisponible;

// Bloque de pruebas de Jest
describe('Pruebas Unitarias: Sistema de Carrito JOK', () => {
    
    test('Debe calcular el subtotal correctamente (Precio 250 x 2)', () => {
        expect(calcularSubtotal(250, 2)).toBe(500);
    });

    test('Debe calcular el IVA del 16% correctamente', () => {
        const subtotal = 500;
        expect(calcularIVA(subtotal)).toBe(80);
    });

    test('No debe permitir agregar más libros de los que hay en stock', () => {
        const stockEnMySQL = 10;
        const pedidoCliente = 15;
        expect(validarStock(pedidoCliente, stockEnMySQL)).toBe(false);
    });
});