-- Búsqueda del token por hash (el enlace solo lleva el token; el email se resuelve aquí)
-- y unicidad defensiva: dos tokens jamás comparten hash.
-- @UP
ALTER TABLE password_resets ADD UNIQUE KEY uq_resets_token (token_hash);

-- @DOWN
ALTER TABLE password_resets DROP INDEX uq_resets_token;
