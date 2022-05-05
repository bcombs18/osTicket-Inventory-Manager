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

class AssetMysqlSearchBackend extends \MysqlSearchBackend {
    function find($query, \QuerySet $criteria, $addRelevance=true) {
        global $thisstaff;

        // MySQL usually doesn't handle words shorter than three letters
        // (except with special configuration)
        if (strlen($query) < 3)
            return $criteria;

        $criteria = clone $criteria;

        $mode = ' IN NATURAL LANGUAGE MODE';

        // According to the MySQL full text boolean mode, this grammar is
        // assumed:
        // see http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html
        //
        // PREOP    = [<>~+-]
        // POSTOP   = [*]
        // WORD     = [\w][\w-]*
        // TERM     = PREOP? WORD POSTOP?
        // QWORD    = " [^"]+ "
        // PARENS   = \( { { TERM | QWORD } { \s+ { TERM | QWORD } }+ } \)
        // EXPR     = { PREOP? PARENS | TERM | QWORD }
        // BOOLEAN  = EXPR { \s+ EXPR }*
        //
        // Changing '{' for (?: and '}' for ')', collapsing whitespace, we
        // have this regular expression
        $BOOLEAN = '(?:[<>~+-]?\((?:(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))+)\)|[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?\((?:(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))+)\)|[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))*';

        // Require the use of at least one operator and conform to the
        // boolean mode grammar
        $T = array();
        if (preg_match('`(^|\s)["()<>~+-]`u', $query, $T)
            && preg_match("`^{$BOOLEAN}$`u", $query, $T)
        ) {
            // If using boolean operators, search in boolean mode. This regex
            // will ensure proper placement of operators, whitespace, and quotes
            // in an effort to avoid crashing the query at MySQL
            $query = $this->quote($query);
            $mode = ' IN BOOLEAN MODE';
        }
        #elseif (count(explode(' ', $query)) == 1)
        #    $mode = ' WITH QUERY EXPANSION';
        $search = 'MATCH (Z1.title, Z1.content) AGAINST ('.db_input($query).$mode.')';

        switch ($criteria->model) {
            case false:
            case 'model\Asset':
                $criteria->extra(array(
                    'select' => array(
                        '__relevance__' => 'Z1.`relevance`',
                    ),
                    'tables' => array(
                        str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                            "(SELECT Z6.`asset_id` as `asset_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:inventory_asset` Z6 ON (Z6.`asset_id` = Z1.`object_id` and Z1.`object_type` = 'G')  WHERE {}) Z1"),
                    ),
                ));
                $criteria->filter(array('asset_id' => new \SqlCode('Z1.`asset_id`')));
                break;
            case 'User':
                $criteria->extra(array(
                    'select' => array(
                        '__relevance__' => 'Z1.`relevance`',
                    ),
                    'tables' => array(
                        str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                            "(SELECT Z6.`id` as `user_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:user` Z6 ON (Z6.`id` = Z1.`object_id` and Z1.`object_type` = 'U') LEFT JOIN `:organization` Z7 ON (Z7.`id` = Z1.`object_id` AND Z7.`id` = Z6.`org_id` AND Z1.`object_type` = 'O') WHERE {}) Z1"),
                    )
                ));
                $criteria->filter(array('id'=>new SqlCode('Z1.`user_id`')));
                break;
        }

        // TODO: Ensure search table exists;
        if (false) {
            // TODO: Create the search table automatically
            // $class::createSearchTable();
        }

        $this->IndexOldStuff();

        return $criteria;
    }

    function IndexOldStuff() {
        $class = get_class();
        $auto_create = function($db_error) use ($class) {

            if ($db_error != 1146)
                // Perform the standard error handling
                return true;

            // Create the search table automatically
            $class::__init();

        };

        // ASSETS ------------------------------------

        $sql = "SELECT A1.`asset_id` FROM `".INVENTORY_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`asset_id` = A2.`object_id` AND A2.`object_type`='G')
            WHERE A2.`object_id` IS NULL";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $asset = Asset::lookup($row[0]);
            $cdata = $asset->getDynamicData();
            $content = array();

            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('G', $asset->getId(),
                \Format::searchable($asset->getHostname()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return;
        }
    }
}