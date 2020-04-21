.. include:: ../Includes.txt



.. _installation:

============
Installation
============

Target group: **Administrators**

.. important::

   Use version 1.x for TYPO3 CMS 8.7 and 9.5 LTS and 2.x for TYPO3 CMS 10.4 LTS.

The extension needs to be installed as any other extension of TYPO3 CMS:

#. Switch to the module "Extension Manager".

#. Get the extension

   #. **Get it from the Extension Manager:** Press the "Retrieve/Update"
      button and search for the extension key *spreadsheets* and import the
      extension from the repository.

   #. **Get it from typo3.org:** You can always get current version from
      `http://typo3.org/extensions/repository/view/spreadsheets/current/
      <http://typo3.org/extensions/repository/view/spreadsheets/current/>`_ by
      downloading either the t3x or zip version. Upload
      the file afterwards in the Extension Manager.

   #. **Use composer**: execute `composer req hoogi91/spreadsheets` where your `composer.json` is located.

.. hint::

   You can also refer to general TYPO3 documentation, for example the
   :ref:`t3install:extension-installation`.

Latest version from git
-----------------------
You can get the latest version from git by using the git command:

.. code-block:: bash

   git clone git@github.com:hoogi91/spreadsheets.git

Preparation: Include static TypoScript
--------------------------------------

The extension ships some TypoScript code which needs to be included.

#. Switch to the root page of your site.

#. Switch to the **Template module** and select *Info/Modify*.

#. Press the link **Edit the whole template record** and switch to the tab *Includes*.

#. Select **News (news)** at the field *Include static (from extensions):*

.. figure:: ../Images/typoscript-configuration.png
   :class: with-shadow
   :alt: Static Typoscript Configuration
   :width: 300px
