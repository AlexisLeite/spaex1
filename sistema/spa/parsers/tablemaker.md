
# Definicion de estructura

## La estructura pasada al tablemaker debe ser:

- [thead, tbody, tfoot], //no siendo necesario incluir los 3 roles.
- [row1, row2, ...] //siendo *cada* nodo la definicion de un row

Al establecer la estructura de una tabla, la definicion puede hacerse con cualquiera de estas estructuras pero **no con una mezcla de ellas**. Esto es: 

- **Si se establecen roles**, todos los nodos primarios del array de la tabla deben ser roles. 
- **Si no se establecen roles**, todos los nodos primarios son rows que iran dentro del rol de tbody.

Si se pasara un array con nodos primarios roles y nodos primarios row, esto derivaria en un [Error](http://papascom/).

- Se debe tener cuidado que al modificar los datos de la tabla, **se debe respetar el modo en el que se genero la misma**. Es decir, si se crea una tabla a partir de una estructura array que define roles, los cambios a dicha tabla deben ser siempre respetando que la misma **tenga** roles. Se pueden *eliminar* roles, pero no se puede establecer el array como array de rows.

- Por el contrario, si la tabla empezo en modo row, no se puede luego cambiar la tabla para que contenga una estructura de tipo roles. 

##### Ambas operaciones terminaran siempre en un error

### Definicion de roles:
Para definir los roles de thead, tbody o tfoot, se debera pasar un array por cada rol que se desee definir, con la siguiente estructura:
#### La estructura para definir los roles sera:

```js
[  
role: thead | tbody | tfoot,  
rows: [array de rows]  
id:
style:
class:
]
````

### Definicion de rows:
Los rows pueden ser definidos por:
- Array asociativo que establezca propiedades del row.
- Array de celdas.

#### La estructura para definir las propiedades del row sera:

```js
[
id,
class,
style,
nodes: [array de nodes]
]
```

### Definicion de node:
Los nodes seran las celdas de la tabla. Los mismos pueden ser definidos bien por un array asociativo que establezca sus propiedades o por un string, que sera el valor que se mostrara en la tabla.

#### La estructura para definir las propiedades del node sera:

```js
[
mode: href | value (mas adelante se crearan mas modes),
	Si href:
		href: url del enlace,
		title: titulo del enlace,
		text: texto a mostrar

	Si value
		text: Texto a mostrar

th: true | false, establece si el nodo es de tipo th o td
colspan: #, establece el colspan del nodo
style,
id,
class
]
```