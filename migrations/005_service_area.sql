-- Add Atlanta and Tampa to the published service area. Safe to run more than once.

UPDATE settings
SET service_area = 'Valdosta / Lowndes County, Atlanta, and Tampa'
WHERE id = 1
  AND service_area = 'Valdosta, Lowndes County, Hahira, and Remerton, Georgia';
