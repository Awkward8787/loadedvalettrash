-- Resident opt-in price is $20 a month (2000 cents). Was $35 (3500 cents).
-- Updates the settings default and the current settings row only when it is
-- still the old default. Does not change invoices or property-specific prices.

ALTER TABLE settings
  MODIFY COLUMN opt_in_price_cents INT UNSIGNED NOT NULL DEFAULT 2000;

UPDATE settings
SET opt_in_price_cents = 2000
WHERE id = 1 AND opt_in_price_cents = 3500;
