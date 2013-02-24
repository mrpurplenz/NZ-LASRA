These files are a php implimentation of a selection of analytical solutions for unsteady state heat transfer.

It was done so that online calculators of unsteady state heat transfer could be made.

They were prepared for NZ LASRA by Dr Richard Edmonds


To run these php files you will need to copy them into a webserver running php5.0 and for graphiong you will need to enable GD_2 in your php.ini

With your favourite subversion software checkout the three php files into your webserver

View using your favourite browser

How can find a nice how-to for building your own web server at:
http://www.the-web-book.com/build-your-own-webserver.html

Once your webserver is up and running clone the repository from the terminal command line with
sudo git clone https://github.com/mrpurplenz/NZ-LASRA.git

Then copy the php files into a suitible web folder with
sudo mkdir /var/www/ht/
sudo cp Unsteady\ state\ heat\ transfer/php/*.php /var/www/ht

The online unsteadstate heat transfer programs will then be available on your webserver
