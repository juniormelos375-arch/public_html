-- Execute somente se a coluna deleted_at ainda não existir.
ALTER TABLE photos ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER created_at;
CREATE INDEX idx_photos_deleted_at ON photos (deleted_at);
