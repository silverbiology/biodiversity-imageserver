CREATE OR REPLACE VIEW bis_dev.`geographyView` AS SELECT t.`geographyId` geographyId, t.`name` Country, t1.`name` StateProvince, t2.`name` County, t3.`name` Locality FROM bis_dev.`geography` t LEFT OUTER JOIN bis_dev.`geography` t1 ON t.`geographyId` = t1.`parentId` LEFT OUTER JOIN bis_dev.`geography` t2 ON t.`geographyId` = t1.`parentId` AND t1.`geographyId` = t2.`parentId` LEFT OUTER JOIN bis_dev.`geography` t3 ON t.`geographyId` = t1.`parentId` AND t1.`geographyId` = t2.`parentId`  AND t2.`geographyId` = t3.`parentId` WHERE t.`parentId` = 0;