-- =====================================================================
-- FinanTrack - Gestor Web de Finanzas Personales
-- Script de creación de la base de datos (MySQL / MariaDB - XAMPP)
--
-- Uso: phpMyAdmin > Importar > seleccionar este archivo
--      (o desde consola: mysql -u root < database/finantrack.sql)
--
-- ¡ATENCIÓN! Este script BORRA y vuelve a crear la base de datos.
--
-- Usuario administrador de prueba (solo para desarrollo local):
--   correo:     admin@finantrack.com
--   contraseña: Adminda2b33!
-- =====================================================================

DROP DATABASE IF EXISTS finantrack;
CREATE DATABASE finantrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finantrack;

-- ---------------------------------------------------------------------
-- 1. ROLES  (1 = Administrador, 2 = Usuario registrado)
--    El "usuario no autenticado" es cualquier visitante sin sesión,
--    por eso no necesita registro en esta tabla.
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id     TINYINT UNSIGNED PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DIVISAS  (catálogo administrado por el admin)
-- ---------------------------------------------------------------------
CREATE TABLE divisas (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo  CHAR(3)     NOT NULL UNIQUE,          -- MXN, USD, EUR
  nombre  VARCHAR(50) NOT NULL,
  simbolo VARCHAR(5)  NOT NULL,
  activo  TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. USUARIOS
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(60)  NOT NULL,
  apellidos     VARCHAR(80)  NOT NULL,
  correo        VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() de PHP
  divisa_id     INT UNSIGNED NOT NULL,
  rol_id        TINYINT UNSIGNED NOT NULL DEFAULT 2,
  activo        TINYINT(1)   NOT NULL DEFAULT 1, -- el admin puede desactivar cuentas
  ultimo_acceso DATETIME     NULL,
  creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_divisa FOREIGN KEY (divisa_id) REFERENCES divisas(id),
  CONSTRAINT fk_usuarios_rol    FOREIGN KEY (rol_id)    REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. MÉTODOS DE PAGO  (catálogo administrado por el admin)
-- ---------------------------------------------------------------------
CREATE TABLE metodos_pago (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE,
  activo TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. CATEGORÍAS
--    usuario_id NULL  -> categoría global (la administra el admin)
--    usuario_id = X   -> categoría privada del usuario X
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  nombre     VARCHAR(50) NOT NULL,
  tipo       ENUM('ingreso','gasto') NOT NULL,
  color      CHAR(7)     NOT NULL DEFAULT '#2563eb',  -- formato #RRGGBB
  icono      VARCHAR(10) NOT NULL DEFAULT '💰',
  activo     TINYINT(1)  NOT NULL DEFAULT 1,
  CONSTRAINT fk_categorias_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. TRANSACCIONES  (ingresos y gastos)
--    activo = 0 -> borrado lógico (se conserva el registro)
--    ON DELETE RESTRICT -> no se puede eliminar una categoría o método
--    de pago que tenga transacciones ligadas.
-- ---------------------------------------------------------------------
CREATE TABLE transacciones (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT UNSIGNED NOT NULL,
  categoria_id   INT UNSIGNED NOT NULL,
  metodo_pago_id INT UNSIGNED NOT NULL,
  tipo           ENUM('ingreso','gasto') NOT NULL,
  monto          DECIMAL(12,2) NOT NULL CHECK (monto > 0),
  fecha          DATE          NOT NULL,
  notas          VARCHAR(255)  NULL,
  activo         TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_trans_usuario   FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE CASCADE,
  CONSTRAINT fk_trans_categoria FOREIGN KEY (categoria_id)   REFERENCES categorias(id)   ON DELETE RESTRICT,
  CONSTRAINT fk_trans_metodo    FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id) ON DELETE RESTRICT,
  INDEX idx_trans_usuario_fecha (usuario_id, fecha)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. PRESUPUESTOS MENSUALES  (tope de gasto por categoría y mes)
-- ---------------------------------------------------------------------
CREATE TABLE presupuestos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  anio         SMALLINT UNSIGNED NOT NULL,
  mes          TINYINT UNSIGNED  NOT NULL CHECK (mes BETWEEN 1 AND 12),
  monto_limite DECIMAL(12,2)     NOT NULL CHECK (monto_limite > 0),
  CONSTRAINT fk_pres_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE CASCADE,
  CONSTRAINT fk_pres_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_presupuesto (usuario_id, categoria_id, anio, mes)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. METAS DE AHORRO
-- ---------------------------------------------------------------------
CREATE TABLE metas_ahorro (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT UNSIGNED NOT NULL,
  nombre         VARCHAR(80)   NOT NULL,
  monto_objetivo DECIMAL(12,2) NOT NULL CHECK (monto_objetivo > 0),
  monto_actual   DECIMAL(12,2) NOT NULL DEFAULT 0,
  fecha_limite   DATE          NOT NULL,
  creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_metas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS INICIALES (semilla)
-- =====================================================================
INSERT INTO roles (id, nombre) VALUES
  (1, 'Administrador'),
  (2, 'Usuario');

INSERT INTO divisas (codigo, nombre, simbolo) VALUES
  ('MXN', 'Peso Mexicano', '$'),
  ('USD', 'Dólar Estadounidense', 'US$'),
  ('EUR', 'Euro', '€');

INSERT INTO metodos_pago (nombre) VALUES
  ('Efectivo'),
  ('Tarjeta de Débito'),
  ('Tarjeta de Crédito'),
  ('Transferencia');

-- Categorías globales (usuario_id = NULL)
INSERT INTO categorias (usuario_id, nombre, tipo, color, icono) VALUES
  (NULL, 'Salario',         'ingreso', '#16a34a', '💼'),
  (NULL, 'Ventas',          'ingreso', '#0d9488', '🛒'),
  (NULL, 'Otros ingresos',  'ingreso', '#65a30d', '💵'),
  (NULL, 'Alimentación',    'gasto',   '#f97316', '🍽️'),
  (NULL, 'Vivienda',        'gasto',   '#7c3aed', '🏠'),
  (NULL, 'Transporte',      'gasto',   '#0284c7', '🚌'),
  (NULL, 'Servicios',       'gasto',   '#eab308', '💡'),
  (NULL, 'Salud',           'gasto',   '#dc2626', '🩺'),
  (NULL, 'Entretenimiento', 'gasto',   '#db2777', '🎬'),
  (NULL, 'Educación',       'gasto',   '#2563eb', '📚');

-- Administrador de prueba (la contraseña está en el encabezado del archivo)
INSERT INTO usuarios (nombre, apellidos, correo, password_hash, divisa_id, rol_id) VALUES
  ('Admin', 'FinanTrack', 'admin@finantrack.com',
   '$2y$12$EZqmHUAZZepl2dile.Yw8uUhcCh8ZLBDOQrtgyYjgA58/mYHYWMzq', 1, 1);
