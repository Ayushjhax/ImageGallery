<?php

use SilverStripe\CMS\Model\Folder;
use SilverStripe\CMS\Controllers\Controller;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\AssetAdmin\Forms\UploadField;

class ImageGalleryAlbum extends DataObject
{
    private static $db = [
        'AlbumName' => 'Varchar(255)',
        'Description' => 'Text',
    ];

    private static $has_one = [
        'CoverImage' => Image::class,
        'ImageGalleryPage' => ImageGalleryPage::class,
        'Folder' => Folder::class,
    ];

    private static $has_many = [
        'GalleryItems' => ImageGalleryItem::class,
    ];

    public function getCMSFields()
    {
        $fields = FieldList::create(
            TextField::create('AlbumName', _t('ImageGalleryAlbum.ALBUMTITLE', 'Album Title')),
            TextareaField::create('Description', _t('ImageGalleryAlbum.DESCRIPTION', 'Description')),
            UploadField::create('CoverImage', _t('ImageGalleryAlbum.COVERIMAGE', 'Cover Image'))
        );

        return $fields;
    }

    public function Link()
    {
        $folder = $this->Folder();
        $name = $folder ? $folder->Name : $this->FolderID;

        return $this->ImageGalleryPage()->Link("album/$name");
    }

    public function LinkingMode()
    {
        return Controller::curr()->getRequest()->param('ID') == $this->Folder()->Name ? 'current' : 'link';
    }

    public function ImageCount()
    {
        return ImageGalleryItem::get()->filter(['AlbumID' => $this->ID])->count();
    }

    public function FormattedCoverImage()
    {
        return $this->CoverImage()->CroppedImage(
            $this->ImageGalleryPage()->CoverImageWidth,
            $this->ImageGalleryPage()->CoverImageHeight
        );
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (isset($_POST['AlbumName'])) {
            $clean_name = SiteTree::generateURLSegment($_POST['AlbumName']);
            if ($this->FolderID) {
                $folder = $this->Folder();
                $folder->setName($clean_name);
                $folder->Title = $clean_name;
                $folder->write();
            } else {
                $folder = Folder::findOrMake("image-gallery/{$this->ImageGalleryPage()->RootFolder()->Name}/$clean_name");
                $this->FolderID = $folder->ID;
            }
        }
    }

    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $this->GalleryItems()->removeAll();
        $folder = $this->Folder();
        if ($folder) {
            $folder->delete();
        }
    }
}
