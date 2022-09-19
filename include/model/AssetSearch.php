<?php

use \model\AssetForm;

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
            case 'I':
            default:
                return 'model\Asset';
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

    static function getExportableFields() {
        $cdata = $fields = array();
        foreach (AssetForm::getInstance()->getFields() as $f) {
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
                $exportableFields = AssetSavedSearch::getExportableFields();
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

        $query->order_by('host_name');

        // Distinct ticket_id to avoid duplicate results
        $query->distinct('asset_id');

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

        $queue = new AssetAdhocSearch(array(
            'id' => "adhoc,$key",
            'root' => 'I',
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
\QueueColumnFilter::register('\AssetLinkWithPreviewFilter', __('Link'));

class AssigneeLinkFilter
    extends \TicketLinkFilter {
    static $id = 'link:assignee';
    static $desc = /* @trans */ "Assignee Link";

    function filter($text, $row) {
        $text = json_decode($text->value, true);
        $link = $this->getLink($text['id']);
        return sprintf('<a style="display: inline" href="%s">%s</a>', $link, $text['name']);
    }

    function getLink($id) {
        return \User::getLink($id);
    }
}
\QueueColumnFilter::register('\AssigneeLinkFilter', __('Link'));

require_once(INCLUDE_DIR.'class.config.php');
class AssetMySqlSearchConfig extends \Config {
    var $table = CONFIG_TABLE;

    function __construct() {
        parent::__construct("assetmysqlsearch");
    }
}

class AssetMysqlSearchBackend extends \MySqlSearchBackend {

    static $id = 'assetmysql';

    function bootstrap() {
        \Signal::connect('cron', array($this, 'IndexOldStuff'));
    }

    function getConfig() {
        if (!isset($this->config))
            $this->config = new AssetMySqlSearchConfig();
        return $this->config;
    }

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
            case 'Ticket':
                if ($addRelevance) {
                    $criteria = $criteria->extra(array(
                        'select' => array(
                            '__relevance__' => 'Z1.`relevance`',
                        ),
                    ));
                }
                $criteria->extra(array(
                    'tables' => array(
                        str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                            "(SELECT COALESCE(Z3.`object_id`, Z5.`ticket_id`, Z8.`ticket_id`) as `ticket_id`, Z1.relevance FROM (SELECT Z1.`object_id`, Z1.`object_type`, {} AS `relevance` FROM `:_search` Z1 WHERE {} ORDER BY relevance DESC) Z1 LEFT JOIN `:thread_entry` Z2 ON (Z1.`object_type` = 'H' AND Z1.`object_id` = Z2.`id`) LEFT JOIN `:thread` Z3 ON (Z2.`thread_id` = Z3.`id` AND (Z3.`object_type` = 'T' OR Z3.`object_type` = 'C')) LEFT JOIN `:ticket` Z5 ON (Z1.`object_type` = 'T' AND Z1.`object_id` = Z5.`ticket_id`) LEFT JOIN `:user` Z6 ON (Z6.`id` = Z1.`object_id` and Z1.`object_type` = 'U') LEFT JOIN `:organization` Z7 ON (Z7.`id` = Z1.`object_id` AND Z7.`id` = Z6.`org_id` AND Z1.`object_type` = 'O') LEFT JOIN `:ticket` Z8 ON (Z8.`user_id` = Z6.`id`)) Z1"),
                    ),
                ));
                $criteria->extra(array('order_by' => array(array(new SqlCode('Z1.relevance', 'DESC')))));

                $criteria->filter(array('ticket_id'=>new SqlCode('Z1.`ticket_id`')));
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

            case 'Organization':
                $criteria->extra(array(
                    'select' => array(
                        '__relevance__' => 'Z1.`relevance`',
                    ),
                    'tables' => array(
                        str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                            "(SELECT Z2.`id` as `org_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:organization` Z2 ON (Z2.`id` = Z1.`object_id` AND Z1.`object_type` = 'O') WHERE {}) Z1"),
                    )
                ));
                $criteria->filter(array('id'=>new SqlCode('Z1.`org_id`')));
                break;

            case 'model\Asset':
                $criteria->extra(array(
                    'select' => array(
                        '__relevance__' => 'Z1.`relevance`',
                    ),
                    'tables' => array(
                        str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                            "(SELECT Z6.`asset_id` as `asset_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:inventory_asset` Z6 ON (Z6.`asset_id` = Z1.`object_id` and Z1.`object_type` = 'I')  WHERE {}) Z1"),
                    ),
                ));
                $criteria->filter(array('asset_id' => new \SqlCode('Z1.`asset_id`')));
                break;
        }

        // TODO: Ensure search table exists;
        if (false) {
            // TODO: Create the search table automatically
            // $class::createSearchTable();
        }
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

        // THREADS ----------------------------------
        $sql = "SELECT A1.`id`, A1.`title`, A1.`body`, A1.`format` FROM `".THREAD_ENTRY_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='H')
            WHERE A2.`object_id` IS NULL AND (A1.poster <> 'SYSTEM')
            AND (IFNULL(LENGTH(A1.`title`), 0) + IFNULL(LENGTH(A1.`body`), 0) > 0)
            ORDER BY A1.`id` DESC LIMIT 500";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $body = ThreadEntryBody::fromFormattedText($row[2], $row[3]);
            $body = $body->getSearchable();
            $title = Format::searchable($row[1]);
            if (!$body && !$title)
                continue;
            $record = array('H', $row[0], $title, $body);
            if (!$this->__index($record))
                return;
        }

        // TICKETS ----------------------------------

        $sql = "SELECT A1.`ticket_id` FROM `".TICKET_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`ticket_id` = A2.`object_id` AND A2.`object_type`='T')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`ticket_id` DESC LIMIT 300";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            if (!($ticket = Ticket::lookup($row[0])))
                continue;
            $cdata = $ticket->loadDynamicData();
            $content = array();
            foreach ($cdata as $k=>$a)
                if ($k != 'subject' && ($v = $a->getSearchable()))
                    $content[] = $v;
            $record = array('T', $ticket->getId(),
                Format::searchable($ticket->getNumber().' '.$ticket->getSubject()),
                implode("\n", $content));
            if (!$this->__index($record))
                return;
        }

        // USERS ------------------------------------

        $sql = "SELECT A1.`id` FROM `".USER_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='U')
            WHERE A2.`object_id` IS NULL";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $user = User::lookup($row[0]);
            $cdata = $user->getDynamicData();
            $content = array();
            foreach ($user->emails as $e)
                $content[] = $e->address;
            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('U', $user->getId(),
                Format::searchable($user->getFullName()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return;
        }

        // ORGANIZATIONS ----------------------------

        $sql = "SELECT A1.`id` FROM `".ORGANIZATION_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='O')
            WHERE A2.`object_id` IS NULL";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $org = Organization::lookup($row[0]);
            $cdata = $org->getDynamicData();
            $content = array();
            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('O', $org->getId(),
                Format::searchable($org->getName()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return null;
        }

        // KNOWLEDGEBASE ----------------------------

        require_once INCLUDE_DIR . 'class.faq.php';
        $sql = "SELECT A1.`faq_id` FROM `".FAQ_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`faq_id` = A2.`object_id` AND A2.`object_type`='K')
            WHERE A2.`object_id` IS NULL";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            if (!($faq = FAQ::lookup($row[0])))
                continue;
            $q = $faq->getQuestion();
            if ($k = $faq->getKeywords())
                $q = $k.' '.$q;
            $record = array('K', $faq->getId(),
                Format::searchable($q),
                $faq->getSearchableAnswer());
            if (!$this->__index($record))
                return;
        }

        // ASSETS ------------------------------------

        $sql = "SELECT A1.`asset_id` FROM `".INVENTORY_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`asset_id` = A2.`object_id` AND A2.`object_type`='I')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`asset_id` DESC LIMIT 300";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            if(!($asset = \model\Asset::lookup($row[0])))
                continue;
            $cdata = $asset->getDynamicData();
            $content = array();

            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('I', $asset->getId(),
                \Format::searchable($asset->getHostname()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return;
        }

        // FILES ------------------------------------

        // Flush non-full batch of records
        $this->__index(null, true);

        if (!$this->_reindexed) {
            // Stop rebuilding the index
            $this->getConfig()->set('reindex', 0);
        }
    }

    function update($model, $id, $content, $new=false, $attrs=array()) {
        if (!($type=\ObjectModel::getType($model)))
            return;

        if ($model instanceof Ticket)
            $attrs['title'] = $attrs['number'].' '.$attrs['title'];
        elseif ($model instanceof \model\Asset)
            $attrs['host_name'] = $attrs['host_name'].' '.$attrs['assignee'];
        elseif ($model instanceof \User)
            $content .=' '.implode("\n", $attrs['emails']);

        $title = $attrs['host_name'] ?: '';

        if (!$content && !$title)
            return;
        if (!$id)
            return;

        $sql = 'REPLACE INTO '.$this->SEARCH_TABLE
            . ' SET object_type='.db_input($type)
            . ', object_id='.db_input($id)
            . ', content='.db_input($content)
            . ', title='.db_input($title);
        return db_query($sql, false);
    }
}

AssetMysqlSearchBackend::register();

Signal::connect('system.install',
    array('AssetMysqlSearchBackend', '__init'));