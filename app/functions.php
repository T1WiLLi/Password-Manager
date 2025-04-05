<?php

use Models\Core\Entity;
use Zephyrus\Application\Form;

/**
 * Updates the given entity using the form fields that match entity's properties.
 *
 * @param object $entity
 * @param array $ignoreFields
 */
function updateEntity(Entity $entity, Form $form, array $ignoreFields = []): Entity
{
    $formData = $form->getFields();
    $reflection = new \ReflectionClass($entity);

    foreach ($formData as $key => $value) {
        if (in_array($key, $ignoreFields)) {
            continue;
        }

        if ($reflection->hasProperty($key)) {
            $property = $reflection->getProperty($key);
            $property->setAccessible(true);
            $entity->$key = $value;
        }
    }

    return $entity;
}


/**
 * Builds a new entity of the given class name using the form fields.
 * Fields not matching properties will be ignored. Fields not present will be null.
 *
 * @param string $className
 * @param array $ignoreFields
 * @return object
 */
function buildEntity(string $className, Form $form, array $ignoreFields = []): Entity
{
    /** @var Entity $entity */
    $entity = $className::build();
    $formData = $form->getFields();
    $reflection = new \ReflectionClass($entity);

    foreach ($formData as $key => $value) {
        if (in_array($key, $ignoreFields)) {
            continue;
        }

        if ($reflection->hasProperty($key)) {
            $property = $reflection->getProperty($key);
            $property->setAccessible(true);
            $entity->$key = $value;
        }
    }

    return $entity;
}
