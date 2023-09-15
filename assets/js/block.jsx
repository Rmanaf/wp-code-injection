const { __ } = wp.i18n;
const { useEffect } = wp.element;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { Flex, FlexItem, SelectControl, Placeholder, Spinner, PanelBody } = wp.components;

const defaultCodeId = 'code-#########';

// Register the block
wp.blocks.registerBlockType('ci/inject', {
  title: __('Code Injection', 'code-injection'),
  description: __('Inject code snippets in HTML, CSS, and JavaScript.', 'code-injection'),
  icon: 'shortcode',
  category: 'widgets',
  attributes: {
    codeId: {
      type: 'string',
      default: defaultCodeId
    }
  },
  edit: function (props) {

    var codes = _ci.codes || [];

    if (!Array.isArray(codes)) {
      codes = Object.values(codes);
    }

    // Generate options for the select control
    var options = [{ value: defaultCodeId, label: __('— Select —', 'code-injection') }];

    codes.forEach(function (code) {
      options.push({ value: code.value, label: code.title });
    });


    // Add state to track whether we're loading data from the server
    var [isLoading, setIsLoading] = wp.element.useState(false);

    // Add state to store the rendered HTML from the server
    var [renderedHtml, setRenderedHtml] = wp.element.useState('');

    // Use the useEffect hook to handle block selection changes
    useEffect(function () {
      if (props.attributes.codeId !== defaultCodeId) {
        // Start loading data from the server
        setIsLoading(true);
        wp.apiFetch({ path: '/ci/v1/render-code', method: 'POST', data: { codeId: props.attributes.codeId } })
          .then(function (response) {
            // Strip scripts from the HTML
            var strippedHtml = response.html.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
            // Update the block's content with the PHP execution result
            setRenderedHtml(strippedHtml);
            setIsLoading(false);
          });
      } else {
        setIsLoading(false);
        setRenderedHtml('');
      }
    }, [props.attributes.codeId]);

    return (
      <div tabIndex="0">
        <InspectorControls>
          <PanelBody title={__('Code Injection Settings', 'code-injection')}>
            <SelectControl
              label={__('Code ID/Slug:', 'code-injection')}
              value={props.attributes.codeId}
              options={options}
              onChange={(value) => props.setAttributes({ codeId: value })}
            />
          </PanelBody>
        </InspectorControls>
        {isLoading ? (
          <Placeholder>
            <Flex justify="center">
              <FlexItem>
                <Spinner />
              </FlexItem>
            </Flex>
          </Placeholder>
        ) : !isLoading && !renderedHtml && props.attributes.codeId !== defaultCodeId ? (
          <Placeholder>
            <Flex justify="center">
              <FlexItem>
                <p> {__(`Unable to render the code <${props.attributes.codeId}>`, 'code-injection')} </p>
              </FlexItem>
            </Flex>
          </Placeholder>
        ) : !isLoading && (!renderedHtml || props.attributes.codeId === defaultCodeId) ? (
          <Placeholder withIllustration="true"></Placeholder>
        ) : (
          <div className={props.className} dangerouslySetInnerHTML={{ __html: renderedHtml }} ></div>
        )}
      </div>
    );

  },
  save: function (props) {
    // Return null to render the block on the front end with PHP
    return null;
  }
});