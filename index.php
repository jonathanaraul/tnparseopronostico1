	<?php

    $arreglo = parsearApuestasDeportivasAction();

    echo'Ver contenido de la carpeta uploads, si funciono se debieron descargar muchas imagenes, nota esta funcion esta pensada en wampserver porque la ruta es C:/wamp/www/tareaEspecial/uploads';exit;
    function parsearApuestasDeportivasAction() {

		//$em = $this -> getDoctrine() -> getManager();

		$contadorImagenes = 0;
		$contadorPronosticos = 0;
		$contadorNoticias = 0;
        
        //Ruta del listado de noticias de la web a analizar
		$url = 'http://www.apuestas-deportivas.es/pronostico/';

        //Con la funcion file_get_contents se obtiene todo el html que devuelve la web señalada
        $htm = file_get_contents($url);
        //Luego de estudiar el codigo fuente de la web, se descubre que todos los elementos de interes
        //Se encuentran dentro del div LATERAL_IZQUIERDO_DETALLE, por ende se hace un explode para poder
        //Obtener los argumentos que esten antes o despues (adentro del div)
        $str = '<div class="content">';
        $arr = explode($str, $htm);
        $arr = explode('<td', $arr[1]);

        $contenido = $arr[0];


        //Una vez adentro del div de nuestro interes, se observa que todos los enlaces que se necesitan
        //Comienzan por detalle_noticia.php?id=
        $enlaces = explode("http://www.apuestas-deportivas.es/pronostico/", $contenido);

        //Se hace un explode para obtener todos los codigos html que comiencen justo despues de http://www.apuestas-deportivas.es/pronostico/
  
        $idNoticias = array();
        //Se realiza un ciclo for para obtener todos los ids de las noticias
        for ($i=1; $i < count($enlaces); $i++) { 
        	$cadena = $enlaces[$i];
        	//En estas cadenas resultados del ultimo explode hay mas contenido del que nos interesa
        	//Un ejemplo de un link es detalle_noticia.php?id=83851" por ende se sabe que el codigo del id
        	//termina justo antes de la comilla " para esto se usa strpos para ubicar la posicion y luego extraer el id
        	$posicionFinal = strpos($cadena, '"');
			$id = substr($cadena, 0, $posicionFinal);
			//Se comienca en $i-1 porque el primer link que devuelve esta web siempre es vacio y se requieren 
			//son los numeros de los id
            if($i!=(count($enlaces)-1))$idNoticias[$i-1] = $id;
			        }


        /*
        Para el listado de noticias de sector del juego por cada cuadricula hay 3 enlaces,
        uno en el texto, otro en la imagen y otro en el titulo, por ende despues de buscar los links
        apareceran duplicados, para evitar este problema se usa la funcion array_unique que elimina
        los duplicados, pero conserva las antiguas claves, por esto se debe usar foreach y no un for normal
        */
        $idNoticias = array_unique($idNoticias); 
        //var_dump($idNoticias);exit;
        /*
        Se procede a recorrer cada uno de los enlaces para extraer la data y almacenarla en la base de datos
        Se busca recorrer el listado de urls que se genero ejemplo:
        http://sectordeljuego.com/detalle_noticia.php?id=83851
        http://sectordeljuego.com/detalle_noticia.php?id=83850
        http://sectordeljuego.com/detalle_noticia.php?id=83849
        .
        .
        .
        Y asi sucesivamente, se ira recorriendo y obteniendo los textos y descargando una imagen por 
        cada articulo
        */

        foreach ($idNoticias as $key => $value) {
        	ini_set('max_execution_time', 300);
        	// Tener en cuenta que no es igual la ruta lista_noticias a detalle_noticia
        	$urlNoticia = "http://www.apuestas-deportivas.es/pronostico/".$value;
        	
            /*A partir de este punto, hay que analizar de nuevo la estructura del codigo
        	De una noticia en particular, en el caso de noticias hay que extraer 3 elementos siempre
        	Titulo, imagen (descargarla, si hay varias solo la principal) y contenido

        	Luego de analizar la data se observa que este caso sencillo, toda la data de interes se encuentra en el div
        	<div id="NOTICIA_DETALLE">
        	*/        
            //Con la funcion file_get_contents se obtiene todo el html que devuelve la web señalada
        	$html = file_get_contents($urlNoticia);
            $busqueda = '<div class="content">';
            $html = explode($str, $html);
            $html = explode('<p class="postmetadata">', $html[1]);
            $html = $html[0];




        	//Se comienza a extraer los datos de interes, se comienza con el TITULO, el cual se almacena en 
        	//<div id="TITULAR_DETALLE">
            $busqueda = '<h1>';
            $titulo = explode($busqueda, $html);
            $titulo = explode('</h1>', $titulo[1]);
            $titulo = $titulo[0];


       

            $imagen = $titulo[1];//Se almacenan varias lineas de codigo entre ellas la imagen <img>
            $titulo = $titulo[0];//Se almacena unicamente el titulo por ende estaria listo para guardarse

            //Se procede a procesar la IMAGEN
            $busqueda = 'src="';
            $imagen = explode($busqueda, $html);
            $imagen = explode('"', $imagen[1]);
            $imagen = $imagen[0];


            $urlImagen = $imagen;
            $extension = explode(".", $urlImagen);
            $extension = $extension[count($extension) - 1];
            //$path = $this -> container -> getParameter('kernel.root_dir') . '/../web/' . $this -> getUploadDir();
            $nombreImagen = sha1($urlImagen);

            ini_set('max_execution_time', 300);
            $ch = curl_init($urlImagen);
            $fp = fopen(sprintf('%s/%s.%s', 'C:/wamp/www/tnparseopronostico1/uploads', $nombreImagen, $extension), 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

           /* $nombreLimpio = UtilitiesAPI::limpiaNombre($titulo, $this);
            $imagen = new Imagen();
            $imagen -> setNombre($nombreLimpio);
            $imagen -> setTags(UtilitiesAPI::convierteATags($nombreLimpio, $this));
            $imagen -> setPath($nombreImagen . '.' . $extension);
            $imagen -> setIp($this -> container -> get('request') -> getClientIp());
            $em -> persist($imagen);
            $em -> flush();*/
            $contadorImagenes++;
		
            //Se procede a buscar el contenido el cual se encuentra en <div class="CUERPO_DETALLE">
        	$busqueda = '<div class="entry">';
            $contenido = explode($busqueda, $html);
            $contenido = explode('<p class="postmetadata">', $contenido[1]);
            $contenido = $contenido[0];

            $busqueda = 'Evento:';
            $evento = explode($busqueda, $contenido);
            $evento = explode('Fecha:', $evento[1]);
            $fecha = $evento[1];
            $evento = $evento[0];

            $busqueda = '<strong';
            $fecha = explode($busqueda, $fecha);
            $fecha = $fecha[0];
            
            
            $pronostico = explode('Pronóstico de apuestas:', $contenido);
            $pronostico = $pronostico[1];
            $busqueda = '<br';
            $pronostico = explode($busqueda, $pronostico);
            $cuota = $pronostico[1];
            $pronostico= $pronostico[0];

            $cuota = explode('Cuota:', $cuota);
            $cuota = $cuota[1];
            
            $busqueda = 'Stake: ';
            $skate = explode($busqueda, $contenido);
            $skate = explode('Canti', $skate[1]);
            $casaApuestas = $skate[1];
            $skate = $skate[0];

            $busqueda = 'strong>';
            $casaApuestas = explode($busqueda, $casaApuestas);
            $casaApuestas = $casaApuestas[1];
            $busqueda = '<';
            $casaApuestas = explode($busqueda, $casaApuestas);    
            $casaApuestas = $casaApuestas[0];        

            echo'<hr>Pronostico='.$contadorPronosticos;
            echo'<br>El titulo es '.$titulo;
            echo'<br>Imagen '.$imagen;
            echo'<br>la fecha es '.$fecha;
            echo'<br>Evento '.$evento;
            echo'<br>Fecha '.$fecha;
            echo'<br>Pronostico '.$pronostico;
            echo'<br>cuota '.$cuota;
            echo'<br>skate '.$skate;
            echo'<br>casaApuestas '.$casaApuestas;
            echo'<hr>';

            //Se obtienen los datos basicos relacionados con la noticia
            //$usuario = $em -> getRepository('ProjectUserBundle:User') -> find(1);
            //$fuente = $em -> getRepository('ProjectUserBundle:Fuente') -> find(9);
            
            //Se almacena la noticia
            /*$object = new Noticia();
            $object -> setFuente($fuente);
            $object -> setUser($usuario);
            $object -> setNombre($titulo);
            $object -> setImagen($imagen);
            $object -> setDescripcion($contenido);
            $em -> persist($object);
            $em -> flush();
*/

		    $contadorPronosticos++;

        }

        

		$array = array('contadorImagenes' => $contadorImagenes, 'contadorPronosticos' => $contadorPronosticos, 'contadorNoticias' => $contadorNoticias);
		//return $this -> render('ProjectBackBundle:Default:parseo.html.twig', $array);
        return $array;
        }
