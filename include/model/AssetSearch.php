<?php

namespace model;

class AssetSavedQueue extends \SavedQueue {
    function getCurrentSearchFields($source=array(), $criteria=array()) {
        static $basic = array(
            'model\Asset' => array(
                'host_name',
                'model',
                'manufacturer',
                'assignee',
                'location',
                'serial_number',
                'created',
                'lastupdate'
            )
        );

        $all = $this->getSupportedMatches();
        $core = array();

        // Include basic fields for new searches
        if (!isset($this->id))
            foreach ($basic[$this->getRoot()] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        // Add others from current configuration
        foreach ($criteria ?: $this->getCriteria() as $C) {
            list($path) = $C;
            if (isset($all[$path]))
                $core[$path] = $all[$path];
        }

        if (isset($source['fields']))
            foreach ($source['fields'] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        return $core;
    }

    function getBasicQuery() {
        $root = $this->getRoot();
        $query = $root::objects();
        return $this->mangleQuerySet($query);
    }

    function getRoot() {
        switch ($this->root) {
            case 'U':
                return 'model\Asset';
            case 'T':
            default:
                return 'Ticket';
        }
    }

    function getForm($source=null, $searchable=null) {
        $fields = array();
        if (!isset($searchable)) {
            $fields = array(
                ':keywords' => new \TextboxField(array(
                    'id' => 3001,
                    'configuration' => array(
                        'size' => 40,
                        'length' => 400,
                        'autofocus' => true,
                        'classes' => 'full-width headline',
                        'placeholder' => __('Keywords — Optional'),
                    ),
                    'validators' => function($self, $v) {
                        if (mb_str_wc($v) > 3)
                            $self->addError(__('Search term cannot have more than 3 keywords'));
                    },
                )),
            );

            $searchable = $this->getCurrentSearchFields($source);
        }

        foreach ($searchable ?: array() as $path => $field)
            $fields = array_merge($fields, static::getSearchField($field, $path));

        $form = new \AdvancedSearchForm($fields, $source);

        // Field selection validator
        if ($this->criteriaRequired()) {
            $form->addValidator(function($form) {
                if (!$form->getNumFieldsSelected())
                    $form->addError(__('No fields selected for searching'));
            });
        }

        // Load state from current configuraiton
        if (!$source) {
            foreach ($this->getCriteria() as $I) {
                list($path, $method, $value) = $I;
                if ($path == ':keywords' && $method === null) {
                    if ($F = $form->getField($path))
                        $F->value = $value;
                    continue;
                }

                if (!($F = $form->getField("{$path}+search")))
                    continue;
                $F->value = true;

                if (!($F = $form->getField("{$path}+method")))
                    continue;
                $F->value = $method;

                if ($value && ($F = $form->getField("{$path}+{$method}")))
                    $F->value = $value;
            }
        }
        return $form;
    }

    function getStandardColumns() {
        return $this->getColumns(is_null($this->parent));
    }

    function getColumns($use_template=false) {
        if ($this->columns_id
            && ($q = CustomQueue::lookup($this->columns_id))
        ) {
            // Use columns from cited queue
            return $q->getColumns();
        }
        elseif ($this->parent_id
            && $this->hasFlag(self::FLAG_INHERIT_COLUMNS)
            && $this->parent
        ) {
            $columns = $this->parent->getColumns();
            foreach ($columns as $c)
                $c->setQueue($this);
            return $columns;
        }
        elseif (count($this->columns)) {
            return $this->columns;
        }

        // Use the columns of the "Open" queue as a default template
        if ($use_template && ($template = \CustomQueue::lookup(101)))
            return $template->getColumns();

        // Last resort — use standard columns
        foreach (array(
                     \QueueColumn::placeholder(array(
                         "id" => 1,
                         "heading" => "Hostname",
                         "primary" => 'host_name',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                         "filter" => 'link:assetP',
                     )),
                     \QueueColumn::placeholder(array(
                         "id" => 2,
                         "heading" => "Model",
                         "primary" => 'model',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                     )),
                     \QueueColumn::placeholder(array(
                         "id" => 3,
                         "heading" => "Assignee",
                         "primary" => 'assignee',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                         "filter" => 'link:assignee',
                     )),
                     \QueueColumn::placeholder(array(
                         "id" => 4,
                         "heading" => "Location",
                         "primary" => 'location',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                     )),
                 ) as $col)
            $this->addColumn($col);

        return $this->getColumns();
    }
}

class AssetSavedSearch extends AssetSavedQueue {
    function isSaved() {
        return (!$this->__new__);
    }

    function getCount($agent, $cached=true) {
        return 500;
    }
}

class AssetAdhocSearch extends AssetSavedSearch {
    function isSaved() {
        return false;
    }

    function isOwner(\Staff $staff) {
        return $this->ht['staff_id'] == $staff->getId();
    }

    function checkAccess(\Staff $staff) {
        return true;
    }

    function getName() {
        return $this->title ?: $this->describeCriteria();
    }

    static function load($key) {
        global $thisstaff;

        if (strpos($key, 'adhoc') === 0)
            list(, $key) = explode(',', $key, 2);

        if (!$key
            || !isset($_SESSION['advsearch'])
            || !($config=$_SESSION['advsearch'][$key]))
            return null;

        $queue = new \model\AssetAdhocSearch(array(
            'id' => "adhoc,$key",
            'root' => 'U',
            'staff_id' => $thisstaff->getId(),
            'title' => __('Advanced Search'),
        ));
        $queue->config = $config;

        return $queue;
    }
}

class AssetLinkWithPreviewFilter extends \TicketLinkWithPreviewFilter {
    static $id = 'link:assetP';
    static $desc = /* @trans */ "Asset Link with Preview";

    function filter($text, $row) {
        $link = $this->getLink($row);
        return sprintf('<a style="display: inline" class="preview" data-preview="#asset/%d/preview" href="%s">%s</a>',
            $row['asset_id'], $link, $text);
    }

    function mangleQuery($query, $column) {
        static $fields = array(
            'link:asset'   => 'asset_id',
            'link:assetP'  => 'asset_id',
        );

        if (isset($fields[static::$id])) {
            $query = $query->values($fields[static::$id]);
        }
        return $query;
    }

    function getLink($row) {
        return \model\Asset::getLink($row['asset_id']);
    }
}
\QueueColumnFilter::register('\model\AssetLinkWithPreviewFilter', __('Link'));

class AssigneeLinkFilter
    extends \TicketLinkFilter {
    static $id = 'link:assignee';
    static $desc = /* @trans */ "Assignee Link";

    function getLink($row) {
        return \User::getLink($row['assignee']);
    }
}
\QueueColumnFilter::register('\model\AssigneeLinkFilter', __('Link'));