.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**

.. _configuration-tca:

TCA
===

Using the features of this extension is pretty simple. Just use the following configuration in a TCA field of type "text" or "input":

.. code-block:: php

   'config' => [
       'renderType'            => 'spreadsheetInput',
       'uploadField'           => 'tx_spreadsheets_assets',
       'sheetsOnly'            => false,
       'allowColumnExtraction' => true,
   ],


**Field "renderType"**

Set this value always to **spreadsheetInput**. See official :ref:`t3tca:start` for more information about renderType's.


**Field "uploadField"**

The value should point to a upload field in the same dataset (e.g. tt_content's "assets", "image" or "media" fields).
This field should be responsible for uploading files of the following file extensions (all extensions are optional):
- Excel 95 and above (.xls)
- Excel 2007 and above (.xlsx)
- Open Document Format/OASIS (.ods)
- SpreadsheetML / Excel 2003 (.xml)
- Comma-separated values (.csv)
- Hypertext Markup Language Tables (.html)


**Field "sheetsOnly"**

This field can be set to `true` or `false`. Default value is `false`!

On default (value `false`) the user is able to select one file which was previously uploaded in "uploadField" (see above).
After selecting the file the user gets a list of worksheets to select. After selecting the right worksheet the user sees simplified table data and can select the cells he wants ;)

If field value is `true` the user can only select a worksheet and the exact cell selecting is disabled.

.. hint::

   Please note that the default :ref:`content element <editor-content-element>` of this extension
   uses **sheetsOnly => true** and you may override it's TCA value if you want to enable field selection!

**Field "allowColumnExtraction"**
This field can be set to `true` or `false`. Default value is `false`!

If field value is `true` the user can choose whether his selection should be extract by columns or by rows.

.. _configuration-features:

Feature toggle
==============

The following feature toggle can be set in **AdditionalConfiguration.php** to define if :ref:`tabs content element <editor-content-element-tabs>`
is always enabled or disabled.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['spreadsheets.tabsContentElement'] = true;

.. _configuration-tabs-styling:

Tabs Content Element Styling
============================

If you want to change or remove the existing styling of the tabs content element you can write into following Typoscript path.

.. code-block:: typoscript

   // note that this option only takes affect if data processor is TabsProcessor class
   tt_content.spreadsheets_tabs.dataProcessing.10 = Hoogi91\Spreadsheets\DataProcessing\TabsProcessor
   tt_content.spreadsheets_tabs.dataProcessing.10.options.additionalStyles (
      // your different styling comes here
   )
