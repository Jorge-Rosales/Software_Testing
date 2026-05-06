-- =====================================================
--  LIBRERIAS JOK — Base de datos completa
--  Importar en phpMyAdmin → base de datos librerias_jok
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`               INT          PRIMARY KEY AUTO_INCREMENT,
  `nombre`           VARCHAR(100) NOT NULL,
  `email`            VARCHAR(100) NOT NULL UNIQUE,
  `password_hash`    VARCHAR(255) NOT NULL,
  `fecha_registro`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `activo`           TINYINT      DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: categorias
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`          INT          PRIMARY KEY AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL UNIQUE,
  `descripcion` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: libros
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `libros` (
  `id`              INT            PRIMARY KEY AUTO_INCREMENT,
  `titulo`          VARCHAR(150)   NOT NULL,
  `autor`           VARCHAR(100)   NOT NULL,
  `descripcion`     TEXT,
  `precio`          DECIMAL(10,2)  NOT NULL,
  `stock`           INT            NOT NULL DEFAULT 0,
  `categoria_id`    INT            NOT NULL,
  `imagen`          VARCHAR(255)   DEFAULT NULL,
  `fecha_creacion`  DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: carrito
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito` (
  `id`             INT      PRIMARY KEY AUTO_INCREMENT,
  `usuario_id`     INT      NOT NULL,
  `libro_id`       INT      NOT NULL,
  `cantidad`       INT      NOT NULL DEFAULT 1,
  `fecha_agregado` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_libro` (`usuario_id`, `libro_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`libro_id`)   REFERENCES `libros`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: ordenes
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordenes` (
  `id`          INT           PRIMARY KEY AUTO_INCREMENT,
  `usuario_id`  INT           NOT NULL,
  `fecha`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `subtotal`    DECIMAL(10,2) NOT NULL,
  `iva`         DECIMAL(10,2) NOT NULL,
  `total`       DECIMAL(10,2) NOT NULL,
  `estado`      ENUM('completada','cancelada') DEFAULT 'completada',
  `direccion`   TEXT,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: orden_items
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_items` (
  `id`               INT           PRIMARY KEY AUTO_INCREMENT,
  `orden_id`         INT           NOT NULL,
  `libro_id`         INT           NOT NULL,
  `cantidad`         INT           NOT NULL,
  `precio_unitario`  DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`libro_id`) REFERENCES `libros`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: devoluciones
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `devoluciones` (
  `id`          INT      PRIMARY KEY AUTO_INCREMENT,
  `usuario_id`  INT      NOT NULL,
  `libro_id`    INT      NOT NULL,
  `orden_id`    INT      NOT NULL,
  `cantidad`    INT      NOT NULL,
  `fecha`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  `motivo`      TEXT,
  `estado`      ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`libro_id`)   REFERENCES `libros`(`id`),
  FOREIGN KEY (`orden_id`)   REFERENCES `ordenes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: resenas
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `resenas` (
  `id`            INT      PRIMARY KEY AUTO_INCREMENT,
  `usuario_id`    INT      NOT NULL,
  `libro_id`      INT      NOT NULL,
  `calificacion`  INT      NOT NULL CHECK (`calificacion` BETWEEN 1 AND 5),
  `comentario`    TEXT,
  `fecha`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_libro_resena` (`usuario_id`, `libro_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`libro_id`)   REFERENCES `libros`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
--  DATOS DE PRUEBA
-- =====================================================

-- Categorias
INSERT IGNORE INTO `categorias` (`nombre`) VALUES
  ('Ficción'),
  ('No ficción'),
  ('Tecnología'),
  ('Autoayuda'),
  ('Infantil');

-- Libros
INSERT IGNORE INTO `libros` (`titulo`, `autor`, `descripcion`, `precio`, `stock`, `categoria_id`) VALUES
  ('Cien años de soledad',          'Gabriel García Márquez',    'La saga de la familia Buendía a lo largo de siete generaciones en el mítico pueblo de Macondo.',                550.00, 20, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('El principito',                 'Antoine de Saint-Exupéry',  'Un piloto encuentra a un pequeño príncipe caído de un asteroide. Una historia sobre la infancia y los sueños.', 180.00, 35, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('Sapiens',                       'Yuval Noah Harari',         'Cómo Homo sapiens llegó a dominar el mundo. Un recorrido por la historia de la humanidad.',                     480.00, 18, (SELECT id FROM categorias WHERE nombre='No ficción')),
  ('El poder del ahora',            'Eckhart Tolle',             'Una guía para la iluminación espiritual y la vida en el momento presente.',                                     320.00, 25, (SELECT id FROM categorias WHERE nombre='Autoayuda')),
  ('Clean Code',                    'Robert C. Martin',          'Manual de buen estilo en el desarrollo de software. Imprescindible para todo programador.',                     750.00, 10, (SELECT id FROM categorias WHERE nombre='Tecnología')),
  ('Harry Potter y la piedra filosofal', 'J.K. Rowling',         'El primer libro de la saga de un joven mago que descubre sus poderes en Hogwarts.',                            280.00, 40, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('Hábitos atómicos',              'James Clear',               'Cómo los pequeños cambios producen resultados extraordinarios. Sistema práctico para construir buenos hábitos.',395.00, 22, (SELECT id FROM categorias WHERE nombre='Autoayuda')),
  ('El mundo de Sofía',             'Jostein Gaarder',           'Una novela filosófica que narra la historia de la filosofía occidental de manera accesible.',                   310.00, 15, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('Python Crash Course',           'Eric Matthes',              'Introducción práctica y accesible al lenguaje de programación Python.',                                         680.00, 12, (SELECT id FROM categorias WHERE nombre='Tecnología')),
  ('El Alquimista',                 'Paulo Coelho',              'La historia de Santiago, un pastor andaluz en busca de su tesoro personal.',                                    220.00, 30, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('Inteligencia emocional',        'Daniel Goleman',            'Por qué puede importar más que el cociente intelectual en el trabajo y en la vida personal.',                   370.00, 20, (SELECT id FROM categorias WHERE nombre='Autoayuda')),
  ('Design Patterns',               'Gang of Four',              'Los 23 patrones de diseño clásicos para el desarrollo orientado a objetos.',                                    820.00,  8, (SELECT id FROM categorias WHERE nombre='Tecnología')),
  ('Matilda',                       'Roald Dahl',                'La historia de una niña con poderes extraordinarios que enfrenta a la terrible directora Trunchbull.',          160.00, 45, (SELECT id FROM categorias WHERE nombre='Infantil')),
  ('Factfulness',                   'Hans Rosling',              'Diez razones por las que estamos equivocados sobre el mundo y por qué las cosas van mejor de lo que piensas.',  430.00, 14, (SELECT id FROM categorias WHERE nombre='No ficción')),
  ('Eloquent JavaScript',           'Marijn Haverbeke',          'Introducción moderna a la programación con JavaScript para la web.',                                            590.00, 11, (SELECT id FROM categorias WHERE nombre='Tecnología')),
  ('Don Quijote de la Mancha',      'Miguel de Cervantes',       'La primera novela moderna: las aventuras del hidalgo Alonso Quijano y su escudero Sancho Panza.',               450.00, 16, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('El hobbit',                     'J.R.R. Tolkien',            'Las aventuras de Bilbo Bolsón, un hobbit que se embarca en una inesperada odisea.',                             260.00, 28, (SELECT id FROM categorias WHERE nombre='Ficción')),
  ('Breve historia del tiempo',     'Stephen Hawking',           'Una exploración de los grandes interrogantes del universo: el Big Bang, los agujeros negros y el tiempo.',      340.00, 19, (SELECT id FROM categorias WHERE nombre='No ficción')),
  ('Charlie y la fábrica de chocolate', 'Roald Dahl',            'Charlie Bucket gana un boleto dorado para visitar la fábrica del excéntrico señor Willy Wonka.',               155.00, 38, (SELECT id FROM categorias WHERE nombre='Infantil')),
  ('The Pragmatic Programmer',      'David Thomas & Andrew Hunt','Consejos atemporales para convertirse en un mejor desarrollador de software.',                                  710.00,  9, (SELECT id FROM categorias WHERE nombre='Tecnología'));

-- Usuario administrador
-- Contraseña: Admin123
INSERT IGNORE INTO `usuarios` (`nombre`, `email`, `password_hash`) VALUES
  ('Administrador JOK', 'admin@librerias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

SET FOREIGN_KEY_CHECKS = 1;
