-- Run only in the new empty Qwanta MySQL database.
SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@sql_mode,''), 'NO_AUTO_VALUE_ON_ZERO');
START TRANSACTION;
INSERT INTO q_categories(name) VALUES
('Fresh produce'),
('Processed products'),
('Grains & pulses'),
('Spices');
INSERT INTO q_products(id,name,category,description,specifications,local,url,source,author,license,featured) VALUES
(0,'Raw green bananas','Fresh produce','Green bananas for wholesale and commercial requirements.','Export maturity, size and Grade A/B/C','images/product-1.jpg','https://upload.wikimedia.org/wikipedia/commons/4/4a/GREEN_BANANA.jpg','https://commons.wikimedia.org/wiki/File:GREEN_BANANA.jpg','Ranjithkumar Murugesan','CC0',1),
(1,'Yellow bananas','Fresh produce','Ripe bananas with grade and packing options on request.','Ripeness, size and Grade A/B/C','images/product-2.jpg','https://upload.wikimedia.org/wikipedia/commons/b/b7/Fresh_-_yellow_bananas.jpg','https://commons.wikimedia.org/wiki/File:Fresh_-_yellow_bananas.jpg','Re-sendings','CC BY-SA 4.0',0),
(2,'Banana powder','Processed products','Banana powder for food ingredient enquiries.','Grade A/B/C, mesh size and moisture','images/product-3.jpg','https://upload.wikimedia.org/wikipedia/commons/d/d7/Plantain_banana_powder.jpg','https://commons.wikimedia.org/wiki/File:Plantain_banana_powder.jpg','Vis M','CC BY-SA 4.0',0),
(3,'Rice','Grains & pulses','Indian rice varieties, subject to grade and availability.','Basmati / non-basmati; grain length and broken percentage','images/product-4.jpg','https://upload.wikimedia.org/wikipedia/commons/c/c0/Bowl_of_raw_rice_01.jpg','https://commons.wikimedia.org/wiki/File:Bowl_of_raw_rice_01.jpg','Obasi Ogbeyialu Precious','CC BY 4.0',1),
(4,'Onions','Fresh produce','Fresh Indian onions with size and packaging requirements.','Bulb size, variety and grade','images/product-5.jpg','https://upload.wikimedia.org/wikipedia/commons/d/dd/Red_Onions.jpg','https://commons.wikimedia.org/wiki/File:Red_Onions.jpg','Gaurav Dhwaj Khadka','CC BY-SA 4.0',0),
(5,'Spices','Spices','Whole and ground spice sourcing enquiries.','Whole / ground; specify spice and purity','images/product-6.jpg','https://upload.wikimedia.org/wikipedia/commons/9/9e/Condimente.jpg','https://commons.wikimedia.org/wiki/File:Condimente.jpg','MagdaBarascu','CC0',1),
(6,'Pulses','Grains & pulses','Lentils and pulse varieties for bulk enquiries.','Specify lentil, chickpea or other pulse','images/product-7.jpg','https://upload.wikimedia.org/wikipedia/commons/2/2a/Red_lentils_%281%29.jpg','https://commons.wikimedia.org/wiki/File:Red_lentils_%281%29.jpg','Fumikas Sagisavas','CC0',0),
(7,'Peanuts','Grains & pulses','Groundnut sourcing by grade and intended use.','In-shell / shelled; kernel count and grade','images/product-8.jpg','https://upload.wikimedia.org/wikipedia/commons/d/df/Peanuts_%28shelled_raw_marketed%29.jpg','https://commons.wikimedia.org/wiki/File:Peanuts_%28shelled_raw_marketed%29.jpg','David E Mead','CC0',0),
(8,'Dehydrated vegetables','Processed products','Dried vegetable ingredients for food applications.','Specify vegetable, flakes / granules / powder','images/product-9.jpg','https://upload.wikimedia.org/wikipedia/commons/7/7d/Joint_venture_dehydrated_dried_vegetables.jpg','https://commons.wikimedia.org/wiki/File:Joint_venture_dehydrated_dried_vegetables.jpg','Fumikas Sagisavas','CC0',1);
COMMIT;
SELECT COUNT(*) AS products FROM q_products;
SELECT COUNT(*) AS categories FROM q_categories;
