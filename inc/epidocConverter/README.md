# epidocConverter

@version 1.0

@year 2015

@author Philipp Franck

@desc
This is an abstract class, wich is used by both implementations. You can use it, if you want to select the best converter automatically.
 

@tutorial

try {
 $conv = epidocConverter::create($xmlData);
} catch (Exception $e) {
 echo $e->getMessage();
}
 
@see epidocConverterSaxon.class.php and epidocConverterFallback.class.php for more hints
