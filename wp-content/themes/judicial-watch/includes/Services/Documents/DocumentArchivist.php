<?php

namespace Engage\JudicialWatch\Services\Documents;

use Engage\JudicialWatch\JudicialWatch;
use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;
use Spatie\PdfToImage\Exceptions\PdfDoesNotExist;
use Spatie\PdfToImage\Pdf;
use Exception;

class DocumentArchivist extends AbstractHandler {
	/**
	 * @var string
	 */
    public $uploadsDir = "";

	/**
	 * @var string
	 */
    public $uploadsUrl = "";

	/**
	 * @var string
	 */
    public $imagesDir = "";

	/**
	 * @var string
	 */

    public $imagesUrl = "";

	/**
	 * DocumentArchivist constructor.
	 */
	public function __construct() {
		$uploads = wp_get_upload_dir();

		$this->uploadsDir = untrailingslashit($uploads["basedir"]);
		$this->uploadsUrl = untrailingslashit($uploads["baseurl"]);
		$this->imagesDir = $this->uploadsDir . "/document_images";
		$this->imagesUrl = $this->uploadsUrl . "/document_images";

		parent::__construct();
	}

	/**
	 * initialize
	 *
	 * Hooks this object into the WordPress ecosystem of actions and filters.
	 *
	 * @throws HookException
	 *
	 * @return void
	 */
	public function initialize() {
		// most of what the document archive does is handled by WP core.
		// however, the when we add a document to the archive, we do want
		// to create a cover photo image based on the PDF's first page.
		// similarly, if a document is removed:  then we want to delete
		// that image to avoid orphans in the filesystem.

		$this->addFilter("acf/update_value/key=field_5bb61dcaf1a69", "updateFirstPageImage", 10, 2);
		$this->addAction("deleted_post", "removeImage");
	}

	/**
	 * updateFirstPageImage
	 *
	 * When a new PDF file is uploaded, this function extracts its first
	 * page as a JPEG image for use as it's featured image.
	 *
	 * @param int $attachmentId
	 * @param int $documentId
	 *
	 * @return int
	 */
    public function updateFirstPageImage(int $attachmentId, int $documentId) {
		if ($this->shouldUpdateImg($attachmentId, $documentId)) {

			// if we're in here, then our attachment changed since the last
			// time our document was updated.  we can get the paths for our
			// image and PDF document, and create our cover photo from the
			// latter which we save at the former.

			$img = $this->getImgDir($documentId);
			$pdf = $this->getPdf($attachmentId);
			$this->maybeDeleteImg($img);

			if (is_dir($pdf)) {
			    return;
            }

			try {
				if (!$this->createImg($pdf, $img)) {
					throw new Exception("Unable to create cover image for pdf: " . $pdf);
				}
			} catch (Exception $e) {
				JudicialWatch::catcher($e);
			}

			// to make grabbing the cover photo easier, we'll store it in the
			// meta for the document.  we can't make it the "official" featured
			// image because that would require us to put these into the media
			// library and that would bloat the size of the library beyond all
			// usefulness.

			$this->addImgToDocMeta($documentId);
		}

		return $attachmentId;
	}

	/**
	 * shouldUpdateImg
	 *
	 * Returns true if the attachment for this document as changed and,
	 * therefore, we need to update its cover image.
	 *
	 * @param int $attachmentId
	 * @param int $documentId
	 *
	 * @return bool
	 */
    public function shouldUpdateImg(int $attachmentId, int $documentId): bool {

		// the above filter is called right _before_ the attachment field is
		// updated in the database.  so, we can get the current value of that
		// field and compare it against the new attachment ID.  if they're not
		// the same, then we need to make a new cover photo.

		return intval(get_field("attachment", $documentId)) !== $attachmentId;
	}

	/**
	 * getImgDir
	 *
	 * Returns the absolute path to the directory in which we store our
	 * images using the parameter as the filename.
	 *
	 * @param int $documentId
	 *
	 * @return string
	 */
	public function getImgDir(int $documentId): string {
		return sprintf("%s/%d.jpg", $this->imagesDir, $documentId);
	}

	/**
	 * maybeDeleteImg
	 *
	 * Deletes our image file if it exists.
	 *
	 * @param string $img
	 *
	 * @return void
	 */
    public function maybeDeleteImg(string $img) {
		if (is_file($img)) {

			// if the file exists, we delete it.  this ensures that we
			// always have the most recent cover photo for our documents.

			unlink($img);
		}
	}

	/**
	 * getPdf
	 *
	 * Returns the absolute path to the PDF file we want to convert to
	 * an image.
	 *
	 * @param int $attachmentId
	 *
	 * @return string
	 */
    public function getPdf(int $attachmentId): string {
		$pdfName = get_post_meta($attachmentId, "_wp_attached_file", true);
		return sprintf("%s/%s", $this->uploadsDir, $pdfName);
	}

	/**
	 * createImg
	 *
	 * Creates the cover photo for our document.
	 *
	 * @param string $pdf
	 * @param string $img
	 *
	 * @return bool
	 * @throws PdfDoesNotExist
	 */
    public function createImg(string $pdf, string $img): bool {
		$pdfToImage = new Pdf($pdf);
		$pdfToImage->setCompressionQuality(60);
		return $pdfToImage->saveImage($img);
	}

	/**
	 * getImgUrl
	 *
	 * Returns the full web path to the image we create for our PDF.
	 *
	 * @param int $documentId
	 *
	 * @return string
	 */
    public function getImgUrl(int $documentId): string {
		return sprintf("%s/%d.jpg", $this->imagesUrl, $documentId);
	}

	/**
	 * addImgToDocMeta
	 *
	 * Adds our image's URL to the document's metadata.
	 *
	 * @param int $documentId
	 *
	 * @return void
	 */
    public function addImgToDocMeta(int $documentId) {
		update_post_meta($documentId, "_document_cover_image", $this->getImgUrl($documentId));
	}

	/**
	 * removeImage
	 *
	 * Removes the cover image from the disk if the visitor deleted a
	 * document.
	 *
	 * @param int $maybeDocumentId
	 *
	 * @return void
	 */
    public function removeImage(int $maybeDocumentId) {

		// the deleted_post hook fires all the time.  but, since the post
		// is no longer in the database, we can't see if it referenced a
		// document.  but, since images are named with the documents ID,
		// we can simply remove the image if we find it using this ID.
		// even better:  we have methods for these behaviors above!

		$this->maybeDeleteImg($this->getImgDir($maybeDocumentId));
	}
}