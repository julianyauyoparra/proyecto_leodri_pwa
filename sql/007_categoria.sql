-- Categoría principal del producto (home / menú tienda)
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS categoria VARCHAR(50) NOT NULL DEFAULT 'zapatillas' AFTER tags;

UPDATE productos SET categoria = 'zapatillas' WHERE categoria = '' OR categoria IS NULL;
