#Read this note before you start

# Important #

These changes are required to be done on the php.ini file as there are some required functions used in the code that is calling DLL files in the php extensions folder.


# Details #

Uncomment these lines in the php.ini file by removing the semi-colon in the beginning of the line:

  * extension=php\_mbstring.dll
  * extension=php\_mysql.dll
  * extension=php\_mysqli.dll