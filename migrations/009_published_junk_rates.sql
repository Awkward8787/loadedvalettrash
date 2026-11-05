-- Published estimate-only junk ranges. Fills a pair only when both sides are still empty.
-- Does not overwrite a rate the owner already saved. Does not set a move-out price.

UPDATE junk_rates
SET couch_low_cents = 9900, couch_high_cents = 17900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND couch_low_cents IS NULL AND couch_high_cents IS NULL;

UPDATE junk_rates
SET mattress_low_cents = 7900, mattress_high_cents = 14900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND mattress_low_cents IS NULL AND mattress_high_cents IS NULL;

UPDATE junk_rates
SET appliance_low_cents = 9900, appliance_high_cents = 19900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND appliance_low_cents IS NULL AND appliance_high_cents IS NULL;

UPDATE junk_rates
SET table_low_cents = 6900, table_high_cents = 12900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND table_low_cents IS NULL AND table_high_cents IS NULL;

UPDATE junk_rates
SET bags_low_cents = 4900, bags_high_cents = 8900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND bags_low_cents IS NULL AND bags_high_cents IS NULL;

UPDATE junk_rates
SET quarter_low_cents = 14900, quarter_high_cents = 24900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND quarter_low_cents IS NULL AND quarter_high_cents IS NULL;

UPDATE junk_rates
SET half_low_cents = 29900, half_high_cents = 44900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND half_low_cents IS NULL AND half_high_cents IS NULL;

UPDATE junk_rates
SET full_low_cents = 54900, full_high_cents = 79900, updated_at = '2026-11-05 00:00:00'
WHERE id = 1 AND full_low_cents IS NULL AND full_high_cents IS NULL;
