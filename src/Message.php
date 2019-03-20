<?php

namespace App\Extensions\SMSc;

class Message
{
    private $id = 0;
    /** @var string */
    private $time = 0;
    /** @var bool */
    private $translit = false;
    /** @var string */
    private $format = Format::DEFAULT;
    private $sender = false;
    /** @var string */
    private $phones;
    /** @var string */
    private $message;
    /** @var string */
    private $query;
    /** @var array  */
    private $files = [];

    /**
     * Message constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param string|false $from
     * @return $this
     */
    public function from($from)
    {
        $this->sender = $from;

        return $this;
    }

    /**
     * @param array|string $phones
     * @param string $delimiter
     * @return $this
     */
    public function to($phones, $delimiter = ',')
    {
        if (is_array($this->phones)) {
            $this->phones = implode($delimiter, $phones);
        } else {
            $this->phones = $phones;
        }

        return $this;
    }



    public function setCost(int $cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getTranslit()
    {
        return (int)$this->translit;
    }

    /**
     * @param bool $translit
     * @return Message
     */
    public function setTranslit(bool $translit)
    {
        $this->translit = $translit;

        return $this;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $time
     * @return Message
     */
    public function setTime(string $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @param string $format
     * @return Message
     */
    public function setFormat(string $format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return string
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return Message
     */
    public function setQuery(string $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param array $files
     * @return Message
     */
    public function setFiles(array $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }
}
