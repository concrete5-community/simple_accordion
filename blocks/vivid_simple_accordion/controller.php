<?php

namespace Concrete\Package\SimpleAccordion\Block\VividSimpleAccordion;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\File\Tracker\FileTrackableInterface;
use Concrete\Core\Page\Page;
use Concrete\Core\Statistics\UsageTracker\AggregateTracker;
use Concrete\Core\Utility\Service\Xml;

class Controller extends BlockController implements FileTrackableInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btTable
     */
    protected $btTable = 'btVividSimpleAccordion';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btWrapperClass
     */
    protected $btWrapperClass = 'ccm-ui';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btInterfaceWidth
     */
    protected $btInterfaceWidth = 700;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btInterfaceHeight
     */
    protected $btInterfaceHeight = 465;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btExportTables
     */
    protected $btExportTables = ['btVividSimpleAccordion', 'btVividSimpleAccordionItem'];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::$btExportContentColumns
     */
    protected $btExportContentColumns = ['description'];

    /**
     * @var string|null
     */
    protected $framework;

    /**
     * @var string|null
     */
    protected $semantic;

    /**
     * @var \Concrete\Core\Statistics\UsageTracker\AggregateTracker|null
     */
    protected $tracker;

    public function __construct($obj = null, $tracker = null)
    {
        parent::__construct($obj);
        $this->tracker = $tracker;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::getBlockTypeName()
     */
    public function getBlockTypeName()
    {
        return t('Simple Accordion');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::getBlockTypeDescription()
     */
    public function getBlockTypeDescription()
    {
        return t('Add Collapsible Content to your Site');
    }

    public function add()
    {
        $this->addOrEdit();
    }

    public function edit()
    {
        $this->addOrEdit();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::registerViewAssets()
     */
    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('javascript', 'jquery');
    }

    public function view()
    {
        $cn = $this->app->make(Connection::class);
        $items = array_map(
            static function (array $item) {
                $item['description'] = LinkAbstractor::translateFrom($item['description']);

                return $item;
            },
            $cn->fetchAll('SELECT * from btVividSimpleAccordionItem WHERE bID = ? ORDER BY sortOrder', [$this->bID])
        );
        $this->set('items', $items);
        if ($items === []) {
            $page = Page::getCurrentPage();
            $this->set('editMode', $page && !$page->isError() && $page->isEditMode());
        }
        switch($this->semantic){
            case 'h2':
            case 'h3':
            case 'h4':
            case 'span':
                $openTag = '<' . $this->semantic . ' class="panel-title">';
                $closeTag = '</' . $this->semantic . '>';
                break;
            case 'paragraph':
                $openTag = '<p class="panel-title">';
                $closeTag = '</p>';
                break;
            default:
                $openTag = '';
                $closeTag = '';
                break;
        }
        $this->set('openTag', $openTag);
        $this->set('closeTag', $closeTag);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::duplicate()
     */
    public function duplicate($newBID)
    {
        parent::duplicate($newBID);
        $cn = $this->app->make(Connection::class);
        $copyFields = 'title, description, state, sortOrder';
        $cn->executeUpdate(
            "INSERT INTO btVividSimpleAccordionItem (bID, {$copyFields}) SELECT :newBID, {$copyFields} FROM btVividSimpleAccordionItem WHERE btVividSimpleAccordionItem.bID = :oldBID",
            [
                'oldBID' => $this->bID,
                'newBID' => $newBID,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::delete()
     */
    public function delete()
    {
        $cn = $this->app->make(Connection::class);
        $cn->delete('btVividSimpleAccordionItem', ['bID' => $this->bID]);
        parent::delete();
        $this->getTracker()->forget($this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::save()
     */
    public function save($args)
    {
        $cn = $this->app->make(Connection::class);
        $cn->transactional(
            function () use ($cn, $args) {
                parent::save($args);
                $cn->executeUpdate('DELETE from btVividSimpleAccordionItem WHERE bID = ?', [$this->bID]);
                if (isset($args['sortOrder']) && is_array($args['sortOrder'])) {
                    $sortOrder = 0;
                    foreach (array_keys($args['sortOrder']) as $i) {
                        $cn->insert('btVividSimpleAccordionItem', [
                            'bID' => $this->bID,
                            'title' => isset($args['title'][$i]) ? (string) $args['title'][$i] : '',
                            'description' => isset($args['description'][$i]) ? LinkAbstractor::translateTo((string) $args['description'][$i]) : '',
                            'state' => isset($args['state'][$i]) ? trim($args['state'][$i]) : '',
                            'sortOrder' => $sortOrder,
                        ]);
                        $sortOrder++;
                    }
                }
            }
        );
        $blockObject = $this->getBlockObject();
        if ($blockObject) {
            $blockObject->setCustomTemplate(isset($args['framework']) ? $args['framework'] : null);
        }
        $this->getTracker()->track($this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::export()
     */
    public function export(\SimpleXMLElement $blockNode)
    {
        parent::export($blockNode);
        $idNodes = $blockNode->xpath('./data[@table="btVividSimpleAccordionItem"]/record/id');
        if ($idNodes) {
            foreach ($idNodes as $idNode) {
                unset($idNode[0]);
            }
        }
        $sortOrderNodes = $blockNode->xpath('./data[@table="btVividSimpleAccordionItem"]/record/sortOrder');
        if ($sortOrderNodes) {
            foreach ($sortOrderNodes as $sortOrderNode) {
                unset($sortOrderNode[0]);
            }
        }
        if (version_compare(APP_VERSION, '9.4') < 0) {
            $xmlService = $this->app->make(Xml::class);
            $recordNodes = $blockNode->xpath('./data[@table="btVividSimpleAccordionItem"]/record');
            if ($recordNodes) {
                foreach ($recordNodes as $recordNode) {
                    if (!isset($recordNode->description)) {
                        continue;
                    }
                    $description = (string) $recordNode->description;
                    if ($description === '') {
                        continue;
                    }
                    unset($recordNode->description);
                    $xmlService->createCDataNode($recordNode, 'description', LinkAbstractor::export($description));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\File\Tracker\FileTrackableInterface::getUsedCollection()
     */
    public function getUsedCollection()
    {
        return $this->getCollectionObject();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\File\Tracker\FileTrackableInterface::getUsedFiles()
     */
    public function getUsedFiles()
    {
        $cn = $this->app->make(Connection::class);
        $items = $cn->fetchAll('SELECT * from btVividSimpleAccordionItem WHERE bID = ? ORDER BY sortOrder', [$this->bID]);

        return array_merge(
            $this->getUsedFilesImages($items),
            $this->getUsedFilesDownload($items)
        );
    }

    /**
     * @return \Concrete\Core\Statistics\UsageTracker\AggregateTracker
     */
    protected function getTracker()
    {
        if ($this->tracker === null) {
            $this->tracker = $this->app->make(AggregateTracker::class);
        }

        return $this->tracker;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Block\BlockController::importAdditionalData()
     */
    protected function importAdditionalData($b, $blockNode)
    {
        $recordNodes = $blockNode->xpath('./data[@table="btVividSimpleAccordionItem"]/record');
        if ($recordNodes) {
            $cn = $this->app->make(Connection::class);
            $bID = (int) $b->getBlockID();
            $sortOrder = 0;
            foreach ($recordNodes as $recordNode) {
                $cn->insert('btVividSimpleAccordionItem', [
                    'bID' => $bID,
                    'title' => isset($recordNode->title) ? (string) $recordNode->title : '',
                    'description' => isset($recordNode->description) ? LinkAbstractor::import((string) $recordNode->description) : '',
                    'state' => isset($recordNode->state) ? (string) $recordNode->state : '',
                    'sortOrder' => $sortOrder++,
                ]);
            }
        }
    }

    private function getUsedFilesImages(array $items)
    {
        $ids = [];
        $matches = null;
        foreach ($items as $item) {
            if (preg_match_all('/\<concrete-picture[^>]*?fID\s*=\s*[\'"]([^\'"]*?)[\'"]/i', $item['description'], $matches)) {
                foreach ($matches[1] as $match) {
                    $ids[] = (int) $match;
                }
            }
        }

        return $ids;
    }

    /**
     * @return int[]
     */
    private function getUsedFilesDownload(array $items)
    {
        $ids = [];
        $matches = null;
        foreach ($items as $item) {
            if (preg_match_all('(FID_DL_\d+)', $item['description'], $matches)) {
                foreach ($matches[0] as $match) {
                    $ids[] = (int) (explode('_', $match)[2]);
                }
            }
        }

        return $ids;
    }

    private function addOrEdit()
    {
        $this->set('ui', $this->app->make('helper/concrete/ui'));
        $this->set('editor', $this->app->make('editor'));
        if ($this->bID) {
            $cn = $this->app->make(Connection::class);
            $this->set('items', array_map(
                static function (array $item) {
                    $item['description'] = LinkAbstractor::translateFromEditMode($item['description']);

                    return $item;
                },
                $cn->fetchAll('SELECT * from btVividSimpleAccordionItem WHERE bID = ? ORDER BY sortOrder', [$this->bID])
            ));
        } else {
            $this->set('framework', '');
            $this->set('semantic', 'span');
            $this->set('items', []);
        }
    }
}
