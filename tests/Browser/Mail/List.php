<?php

namespace Tests\Browser\Mail;

class MailList extends \Tests\Browser\DuskTestCase
{
    protected function setUp()
    {
        parent::setUp();

        \bootstrap::init_imap();
        \bootstrap::purge_mailbox('INBOX');

        // import email messages
        foreach (glob(TESTS_DIR . 'data/mail/list_00.eml') as $f) {
            \bootstrap::import_message($f, 'INBOX');
        }
    }

    public function testList()
    {
        $this->browse(function ($browser) {
            $this->go('mail');

            $this->assertCount(1, $browser->elements('#messagelist tbody tr'));

            // check message list
            $browser->assertVisible('#messagelist tbody tr:first-child.unread');

            $this->assertEquals('Lines', $browser->text('#messagelist tbody tr:first-child span.subject'));

            // Note: This element icon has width=0, use assertPresent() not assertVisible()
            $browser->assertPresent('#messagelist tbody tr:first-child span.msgicon.unread');

            // List toolbar menu
            $browser->assertVisible('#layout-list .header a.toolbar-button.refresh:not(.disabled)');

            if ($this->isDesktop()) {
                $browser->with('#toolbar-list-menu', function ($browser) {
                    $browser->assertVisible('a.select:not(.disabled)');
                    $browser->assertVisible('a.options:not(.disabled)');

                    $imap = \bootstrap::get_storage();
                    if ($imap->get_threading()) {
                        $browser->assertVisible('a.threads:not(.disabled)');
                    }
                    else {
                        $browser->assertMissing('a.threads');
                    }
                });
            }
            else if ($this->isTablet()) {
                $browser->click('.toolbar-list-button');

                $browser->with('#toolbar-list-menu', function ($browser) {
                    $browser->assertVisible('a.select:not(.disabled)');
                    $browser->assertVisible('a.options:not(.disabled)');

                    $imap = \bootstrap::get_storage();
                    if ($imap->get_threading()) {
                        $browser->assertVisible('a.threads:not(.disabled)');
                    }
                    else {
                        $browser->assertMissing('a.threads');
                    }
                });

                $browser->click(); // hide the popup menu
            }
            else { // phone
                // On phones list options are in the toolbar menu
                $browser->click('.toolbar-menu-button');

                $browser->with('#toolbar-menu', function ($browser) {
                    $browser->assertVisible('a.select:not(.disabled)');
                    $browser->assertVisible('a.options:not(.disabled)');

                    $imap = \bootstrap::get_storage();
                    if ($imap->get_threading()) {
                        $browser->assertVisible('a.threads:not(.disabled)');
                    }
                    else {
                        $browser->assertMissing('a.threads');
                    }
                });

                $this->closeToolbarMenu();
            }
        });
    }

    /**
     * @depends testList
     */
    public function testListSelection()
    {
        $this->browse(function ($browser) {
            if ($this->isPhone()) {
                $browser->click('.toolbar-menu-button');
                $browser->click('#toolbar-menu a.select');
            }
            else if ($this->isTablet()) {
                $browser->click('.toolbar-list-button');
                $browser->click('#toolbar-list-menu a.select');
            }
            else {
                $browser->click('#toolbar-list-menu a.select');
                $browser->assertFocused('#toolbar-list-menu a.select');
            }

            // Popup menu content
            $browser->with('#listselect-menu', function($browser) {
                $browser->assertVisible('a.selection:not(.disabled)');
                $browser->assertVisible('a.select.all:not(.disabled)');
                $browser->assertVisible('a.select.page:not(.disabled)');
                $browser->assertVisible('a.select.unread:not(.disabled)');
                $browser->assertVisible('a.select.flagged:not(.disabled)');
                $browser->assertVisible('a.select.invert:not(.disabled)');
                $browser->assertVisible('a.select.none:not(.disabled)');
            });

            // Close the menu(s) by clicking the page body
            $browser->click();
            $browser->waitUntilMissing('#listselect-menu');

            // TODO: Test selection actions
        });
    }
}
