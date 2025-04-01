<?php

namespace Models\PasswordManager\Brokers;

use Models\Core\Entity;
use stdClass;
use Zephyrus\Database\DatabaseBroker;
use ReflectionClass;
use ReflectionProperty;

/**
 * The Broker class is an extension of the DatabaseBroker and is NOT necessary to use the database.
 * It provides a more convenient way to interact with the database by providing C.R.U.D operations.
 */
abstract class Broker extends DatabaseBroker
{
    protected string $table;

    /**
     * @param string $table The name of the table associated with this broker.
     */
    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = $table;
    }

    /**
     * Retrieves all rows from the table.
     * 
     * @return array An array of stdClass objects.
     */
    public function findAll(): array
    {
        return $this->select("SELECT * FROM {$this->table}");
    }

    /**
     * Finds a row by its primary key ID.
     * 
     * @param int $id The ID of the row to find.
     * @return stdClass|null The raw row as stdClass or null if not found.
     */
    public function findById(int $id): ?stdClass
    {
        return $this->selectSingle("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Deletes a row by its primary key ID.
     * 
     * @param int $id The ID of the row to delete.
     * @return bool True if at least one row was deleted, false otherwise.
     */
    public function delete(int $id): bool
    {
        $this->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
        return $this->getLastAffectedCount() > 0;
    }

    /**
     * Saves the entity to the database by determining whether to insert or update.
     * 
     * @param Entity $entity The entity to save.
     * @return int The row's ID (existing or newly created).
     */
    public function save(Entity $entity): int
    {
        $data = $this->extractEntityDataByReflection($entity);
        if (isset($data['id']) && $data['id']) {
            $this->updateFromArray($data);
            return (int)$data['id'];
        } else {
            return $this->insertFromArray($data);
        }
    }

    /**
     * Updates an existing row in the database.
     *
     * @param array $data The data to update.
     * @return int The number of affected rows.
     * @throws \InvalidArgumentException If the entity does not have an id.
     */
    private function updateFromArray(array $data): int
    {
        if (!isset($data['id']) || !$data['id']) {
            throw new \InvalidArgumentException("Cannot update an entity without an id.");
        }
        $id = $data['id'];
        unset($data['id']);

        if (empty($data)) {
            return 0; // Nothing to update
        }

        $fields = array_keys($data);
        $updateFields = implode(", ", array_map(fn($field) => "$field = ?", $fields));
        $query = "UPDATE {$this->table} SET $updateFields WHERE id = ?";
        $parameters = array_values($data);
        $parameters[] = $id;
        $this->query($query, $parameters);
        return $this->getLastAffectedCount();
    }

    /**
     * Inserts a new row into the database.
     *
     * @param array $data The data to insert.
     * @return int The newly inserted row's ID.
     */
    private function insertFromArray(array $data): int
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        if (empty($data)) {
            throw new \InvalidArgumentException("Cannot insert empty data.");
        }

        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders) RETURNING id";
        $result = $this->selectSingle($query, array_values($data));
        return (int)$result->id;
    }

    /**
     * Extracts entity data using reflection.
     * Safely extracts all initialized properties from the entity.
     * 
     * @param Entity $entity The entity object.
     * @return array The extracted data.
     */
    private function extractEntityDataByReflection(Entity $entity): array
    {
        $data = [];
        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();

            if ($name === 'rawData') {
                continue;
            }

            if (!$property->isInitialized($entity)) {
                continue;
            }

            $value = $property->getValue($entity);

            if ($value === null) {
                continue;
            }

            if (is_scalar($value) || is_array($value)) {
                $data[$name] = $value;
                continue;
            }

            if ($value instanceof \UnitEnum) {
                $data[$name] = $value->value ?? $value->name;
                continue;
            }

            if ($value instanceof Entity) {
                $nestedData = $this->extractEntityDataByReflection($value);
                if (isset($nestedData['id'])) {
                    $data[$name . '_id'] = $nestedData['id'];
                }
            }
        }

        return $data;
    }
}
