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
