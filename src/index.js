import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";

/**
 * Registers a TV Products & Afbeeldingen block with slideshow.
 */
registerBlockType("tv/products-block", {
  title: "TV Products & Afbeeldingen",
  icon: "screenoptions",
  category: "widgets",

  edit() {
    const blockProps = useBlockProps();
    return (
      <div {...blockProps}>
        TV Products & Afbeeldingen will render as a full-screen slideshow on the
        front end.
      </div>
    );
  },

  save() {
    return null;
  },
});
