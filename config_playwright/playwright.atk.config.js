/*
* Automated Testing Kit configuration.
*/
module.exports = {
  operatingMode: 'native',
  drushCmd: "drush",
  articleAddUrl: 'node/add/article',
  contactUsUrl: 'form/contact',
  feedbackUrl: 'contact/feedback',
  logInUrl: 'user/login',
  logOutUrl: 'user/logout',
  imageAddUrl: 'media/add/image',
  mediaDeleteUrl: 'media/{mid}/delete',
  mediaEditUrl: 'media/{mid}/edit',
  mediaList: 'admin/content/media',
  menuAddUrl: 'admin/structure/menu/manage/main/add',
  menuDeleteUrl: 'admin/structure/menu/item/{mid}/delete',
  menuEditUrl: 'admin/structure/menu/item/{mid}/edit',
  menuListUrl: 'admin/structure/menu/manage/main',
  nodeDeleteUrl: 'node/{nid}/delete',
  nodeEditUrl: 'node/{nid}/edit',
  pageAddUrl: 'node/add/page',
  registerUrl: 'user/register',
  resetPasswordUrl: 'user/password',
  termAddUrl: 'admin/structure/taxonomy/manage/tags/add',
  termEditUrl: 'taxonomy/term/{tid}/edit',
  termDeleteUrl: 'taxonomy/term/{tid}/delete',
  termListUrl: 'admin/structure/taxonomy/manage/tags/overview',
  termViewUrl: 'taxonomy/term/{tid}',
  xmlSitemapUrl: 'admin/config/search/xmlsitemap',
  blockAddUrl: 'block/add/basic?theme=ucop',
  feedTypeAddUrl: 'admin/structure/feeds/add',
  feedAddUrl: 'feed/add',
  authDir: 'tests/support',
  dataDir: 'tests/data',
  supportDir: 'tests/support',
  testDir: 'tests',
  email: {
    // Configure email testing using Reroute Email but with Enable rerouting OFF,
    // the module will be enabled, right before the test execution, and get back
    // to the initial state, after test execution.
    // reroute: {
    //   address: '<your namespace>.admin@inbox.testmail.app',
    //   allowed: '*@inbox.testmail.app',
    // },

    // Configure one of the test email providers:
    // for Mailpit:
    provider: 'mailpit',
    url: 'http://{baseURL}:8025',

    // for testmail.app:
    // provider: 'testmail',
    // namespace: '<your namespace>',
    // apiKey: '<your API key>',
  },
  pantheon: {
    isTarget: false,
    site: 'aSite',
    environment: 'dev',
  },
  targetSite: {
    isTarget: false,
    root: null, // optional
    remoteHost: 'localhost',
    remoteUser: null, // optional
    sshOptions: '-p 2222', // optional
  },
}
