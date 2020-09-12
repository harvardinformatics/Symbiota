#Create configuration files from conf template files
echo "Creating dbconfiguration file: /config/dbconnection.php"
cp ../config/dbconnection_template.php ../config/dbconnection.php
echo "Creating Symbiota configuration file: /config/symbini.php"
cp ../config/symbini_template.php ../config/symbini.php
echo "Creating homepage: /index.php"
cp ../index_template.php ../index.php
echo "Creating header include: /includes/header.php"
cp ../includes/header_template.php ../includes/header.php
echo "Creating Left Menu include: /includes/leftmenu.php"
cp ../includes/leftmenu_template.php ../includes/leftmenu.php
echo "Creating footer include: /includes/footer.php"
cp ../includes/footer_template.php ../includes/footer.php
echo "Creating head include: /includes/head.php"
cp ../includes/head_template.php ../includes/head.php
echo "Creating usage policy include: /includes/usagepolicy.php"
cp ../includes/usagepolicy_template.php ../includes/usagepolicy.php


#Adjust file permission to give write access to certain folders and files
echo "Adjusting file permissions"
chmod 777 ../webservices/dwc/rss.xml
chmod -R 777 ../temp
chmod -R 777 ../content/collicon
chmod -R 777 ../content/dwca
chmod -R 777 ../content/geolocate
chmod -R 777 ../content/imglib
chmod -R 777 ../content/lang
chmod -R 777 ../content/logs 
