<?php

class Proikos extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Alex Aragon <alex.aragon@tunqui.pe>',
            [
                'tool_enable' => 'boolean'
            ]
        );
        $this->isAdminPlugin = true;
    }

    /**
     * @return string
     */
    public function getToolTitle(): string
    {
        $title = $this->get_lang('tool_title');

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    /**
     * @return Proikos
     */
    public static function create(): Proikos
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {

    }

    public function uninstall()
    {

    }

}
