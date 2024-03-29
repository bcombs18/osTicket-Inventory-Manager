<?php

use \model\PhoneForm;

class PhoneSavedQueue extends \SavedQueue {
    function getCurrentSearchFields($source=array(), $criteria=array()) {
        static $basic = array(
            'model\Phone' => array(
                'phone_number',
                'phone_model',
                'imei',
                'phone_assignee',
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

    function mangleQuerySet(\QuerySet $qs, $form=false) {
        $qs = clone $qs;
        $searchable = $this->getSupportedMatches();

        // Figure out fields to search on
        foreach ($this->getCriteria() as $I) {
            list($name, $method, $value) = $I;

            // Consider keyword searching
            if ($name === ':keywords') {
                $searcher = new AssetMysqlSearchBackend();
                $qs = $searcher->find($value, $qs, false);
            }
            else {
                // XXX: Move getOrmPath to be more of a utility
                // Ensure the special join is created to support custom data joins
                $name = @static::getOrmPath($name, $qs);

                if (preg_match('/__answers!\d+__/', $name)) {
                    $qs->annotate(array($name => SqlAggregate::MAX($name)));
                }

                // Fetch a criteria Q for the query
                if (list(,$field) = $searchable[$name]) {
                    // Add annotation if the field supports it.
                    if (is_subclass_of($field, 'AnnotatedField'))
                        $qs = $field->annotate($qs, $name);

                    if ($q = $field->getSearchQ($method, $value, $name))
                        $qs = $qs->filter($q);
                }
            }
        }

        return $qs;
    }

    function getRoot() {
        switch ($this->root) {
            case 'P':
            default:
                return 'model\Phone';
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
        if ($use_template && ($template = \CustomQueue::lookup(105)))
            return $template->getColumns();

        // Last resort — use standard columns
        foreach (array(
                     \QueueColumn::placeholder(array(
                         "id" => 1,
                         "heading" => "Phone Model",
                         "primary" => 'phone_model',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                         "filter" => "link:phoneP"
                     )),
                     \QueueColumn::placeholder(array(
                         "id" => 2,
                         "heading" => "IMEI",
                         "primary" => 'imei',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE
                     )),
                     \QueueColumn::placeholder(array(
                         "id" => 3,
                         "heading" => "Phone Assignee",
                         "primary" => 'phone_assignee',
                         "width" => 230,
                         "bits" => \QueueColumn::FLAG_SORTABLE,
                         "filter" => 'link:assignee',
                     ))
                 ) as $col)
            $this->addColumn($col);

        return $this->getColumns();
    }

    static function getExportableFields() {
        $cdata = $fields = array();
        foreach (PhoneForm::getInstance()->getFields() as $f) {
            // Ignore core fields
            if (in_array($f->get('name'), array('priority')))
                continue;
            // Ignore non-data fields
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ?: 'field_'.$f->get('id');
            $key = $name;
            $cdata[$key] = $f->getLocal('label');
        }

        // Standard export fields if none is provided.
        $fields = array() + $cdata;

        return $fields;
    }

    function getExportFields($inherit=true) {

        $fields = array();

        $fields = $this->getExportableFields();

        return $fields;
    }

    function export(\CsvExporter $exporter, $options=array()) {
        global $thisstaff;

        if (!$thisstaff
            || !($query=$this->getQuery())
            || !($fields=$this->getExportFields()))
            return false;

        // Do not store results in memory
        $query->setOption(\QuerySet::OPT_NOCACHE, true);

        // See if we have cached export preference
        if (isset($_SESSION['Export:Q'.$this->getId()])) {
            $opts = $_SESSION['Export:Q'.$this->getId()];
            if (isset($opts['fields'])) {
                $fields = array_intersect_key($fields,
                    array_flip($opts['fields']));
                $exportableFields = PhoneSavedSearch::getExportableFields();
                foreach ($opts['fields'] as $key => $name) {
                    if (is_null($fields[$name]) && isset($exportableFields)) {
                        $fields[$name] = $exportableFields[$name];
                    }
                }
            }
        }

        // Apply columns
        $columns = $this->getExportColumns($fields);
        $headers = array(); // Reset fields based on validity of columns
        foreach ($columns as $column) {
            $query = $column->mangleQuery($query, $this->getRoot());
            $headers[] = $column->getHeading();
        }

        $query->order_by('phone_model');

        // Distinct ticket_id to avoid duplicate results
        $query->distinct('phone_id');

        // Render Util
        $render = function ($row) use($columns) {
            if (!$row) return false;

            $record = array();
            foreach ($columns as $path => $column) {
                $record[] = (string) $column->from_query($row) ?:
                    $row[$path] ?: '';
            }
            return $record;
        };

        $exporter->write($headers);
        foreach ($query as $row)
            $exporter->write($render($row));
    }

    static function getHierarchicalQueues(\Staff $staff, $pid=0,
                                                 $primary=true) {
        $query = static::objects()
            ->annotate(array('_sort' =>  \SqlCase::N()
                ->when(array('sort' => 0), 999)
                ->otherwise(new \SqlField('sort'))))
            ->filter(\Q::any(array(
                'flags__hasbit' => self::FLAG_PUBLIC,
                'flags__hasbit' => static::FLAG_QUEUE,
                'staff_id' => $staff->getId(),
            )))
            ->order_by('parent_id', '_sort', 'title');
        $all = $query->asArray();
        // Find all the queues with a given parent
        $for_parent = function($pid) use ($primary, $all, &$for_parent) {
            $results = [];
            foreach (new \ArrayIterator($all) as $q) {
                if ($q->parent_id != $pid)
                    continue;

                if ($pid == 0 && (
                        ($primary &&  !$q->isAQueue())
                        || (!$primary && $q->isAQueue())))
                    continue;

                $results[] = [ $q, $for_parent($q->getId()) ];
            }

            return $results;
        };

        return $for_parent($pid);
    }
}

class PhoneSavedSearch extends PhoneSavedQueue {
    function isSaved() {
        return (!$this->__new__);
    }

    function getCount($agent, $cached=true) {
        return 500;
    }
}

class PhoneAdhocSearch extends PhoneSavedSearch {
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

        $queue = new PhoneAdhocSearch(array(
            'id' => "adhoc,$key",
            'root' => 'P',
            'staff_id' => $thisstaff->getId(),
            'title' => __('Advanced Search'),
        ));
        $queue->config = $config;

        return $queue;
    }
}

class PhoneLinkWithPreviewFilter extends \TicketLinkWithPreviewFilter {
    static $id = 'link:phoneP';
    static $desc = /* @trans */ "Phone Link with Preview";

    function filter($text, $row) {
        $link = $this->getLink($row);
        return sprintf('<a style="display: inline" class="preview" data-preview="#phone/%d/preview" href="%s">%s</a>',
            $row['phone_id'], $link, $text);
    }

    function mangleQuery($query, $column) {
        static $fields = array(
            'link:phone'   => 'phone_id',
            'link:phoneP'  => 'phone_id',
        );

        if (isset($fields[static::$id])) {
            $query = $query->values($fields[static::$id]);
        }
        return $query;
    }

    function getLink($row) {
        return \model\Phone::getLink($row['phone_id']);
    }
}
\QueueColumnFilter::register('\PhoneLinkWithPreviewFilter', __('Link'));