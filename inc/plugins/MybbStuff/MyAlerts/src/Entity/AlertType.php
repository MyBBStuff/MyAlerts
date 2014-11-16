<?php

/**
 * A single alert type object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_AlertType
{
    /** @var int The ID of the alert type. */
    private $id = 0;
    /** @var string The short code identifying the alert type - eg: 'pm', 'rep'. */
    private $code = '';
    /** @var bool Whether the alert type is enabled. */
    private $enabled = true;

    /**
     * Unserialize an alert type from an array created using toArray().
     *
     * @param array $serialized The serialized alert type.
     *
     * @return MybbStuff_MyAlerts_Entity_AlertType The unserialized alert type.
     */
    public static function unserialize(array $serialized)
    {
        $serialized = array_merge(
            array(
                'id' => 0,
                'code' => '',
                'enabled' => false,
            ),
            $serialized
        );

        $alertType = new static();
        $alertType->setEnabled($serialized['enabled']);
        $alertType->setId($serialized['id']);
        $alertType->setCode($serialized['code']);

        return $alertType;
    }

    /**
     * Serialize the alert type to an array.
     *
     * @return array The seralized alert type.
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'enabled' => $this->getEnabled(),
        );
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id = 0)
    {
        $this->id = (int) $id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = (string) $code;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
    }
}
