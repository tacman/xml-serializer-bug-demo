Demonstration of simple serialization and deserialization to/from XML with attributes and properties.

Currently deserialization is not working as expected.

clone this repo, then

```bash
composer install
bin/console app:bug
```



## Goal: Read and Write nested XML with Attributes

```xml
<?xml version="1.0"?>
<product description="Wool sweater">
  <property name="color">blue</property>
  <property name="size">small</property>
</product>
```

The Product and Properties entities are trivial, created with bin/console make:entity

The Serializer is configured so that Product description and Property name are attributes.

```yaml
# config/serialization.yaml
App\Entity\Product:
  attributes:
    description:
      serialized_name: '@description'
      groups: ['xml']
    properties:
      serialized_name: 'property'
      groups: ['xml']

App\Entity\Property:
  attributes:
    name:
      serialized_name: '@name'
      groups: ['xml']
    value:
      serialized_name: '#'
      groups: ['xml']
```

A simple product is created and dumped, and works perfectly:

```php
$product = (new Product())
    ->setDescription("Wool sweater");
$product
    ->addProperty((new Property())->setName('color')->setValue('blue'))
    ->addProperty((new Property())->setName('size')->setValue('small'))
    ;

$context = [
    'xml_root_node_name' => 'product',
    'groups' => ['xml'],
    'xml_standalone' => false,
    'xml_format_output' => true,
];
$xml = $this->serializer->serialize($product, 'xml', $context);
```

But deserialization doesn't work.

```php
        /** @var Product $product2 */
        $product2 = $this->serializer->deserialize($xml, Product::class, 'xml');

        dump($product2); // nothing is set :-(
```

This website is based on the micro-symfony framework.

