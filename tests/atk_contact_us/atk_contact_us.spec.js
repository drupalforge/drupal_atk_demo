/**
 * atk_contact_us.spec.js
 *
 * Contact Us tests.
 *
 */

/** ESLint directives */
/* eslint-disable import/first */

// Set up Playwright.
import { expect, test } from '@playwright/test'

import * as atkCommands from '../support/atk_commands'
import * as atkUtilities from '../support/atk_utilities'

import playwrightConfig from '../../playwright.config'

const baseUrl = playwrightConfig.use.baseURL

// Import ATK Configuration.
import atkConfig from '../../playwright.atk.config'

// Standard accounts that use user accounts created
// by QA Accounts. QA Accounts are created when the QA
// Accounts module is enabled.
import qaUserAccounts from '../data/qaUsers.json'

test.describe('Contact Us tests.', () => {
  const ctx = {}

  test.beforeAll(() => {
    if (!atkConfig.email?.reroute) {
      return
    }

    // initial_state  = drush cget reroute_email.settings enable
    ctx.enable = atkCommands.getDrupalConfiguration('reroute_email.settings', 'enable')
    ctx.address = atkCommands.getDrupalConfiguration('reroute_email.settings', 'address')
    ctx.allowed = atkCommands.getDrupalConfiguration('reroute_email.settings', 'allowed')
    ctx.mailkeys = atkCommands.getDrupalConfiguration('reroute_email.settings', 'mailkeys')
    ctx.mailkeysSkip = atkCommands.getDrupalConfiguration('reroute_email.settings', 'mailkeys_skip')

    // Remote:drush cset reroute_email.settings enable 1
    atkCommands.setDrupalConfiguration('reroute_email.settings', 'enable', true)
    atkCommands.setDrupalConfiguration('reroute_email.settings', 'address', atkConfig.email.reroute.address)
    atkCommands.setDrupalConfiguration('reroute_email.settings', 'allowed', atkConfig.email.reroute.allowed)
    atkCommands.setDrupalConfiguration('reroute_email.settings', 'mailkeys', '')
    atkCommands.setDrupalConfiguration('reroute_email.settings', 'mailkeys_skip', '')
  })

  test.afterAll(() => {
    if (!atkConfig.email?.reroute) {
      return
    }

    // Remote:drush cset reroute_email.settings enable $INITIAL_STATE
    if (ctx.enable !== undefined) {
      atkCommands.setDrupalConfiguration('reroute_email.settings', 'enable', ctx.enable)
    }
    if (ctx.address !== undefined) {
      atkCommands.setDrupalConfiguration('reroute_email.settings', 'address', ctx.address)
    }
    if (ctx.allowed !== undefined) {
      atkCommands.setDrupalConfiguration('reroute_email.settings', 'allowed', ctx.allowed)
    }
    if (ctx.mailkeys !== undefined) {
      atkCommands.setDrupalConfiguration('reroute_email.settings', 'mailkeys', ctx.mailkeys)
    }
    if (ctx.mailkeysSkip !== undefined) {
      atkCommands.setDrupalConfiguration('reroute_email.settings', 'mailkeys_skip', ctx.mailkeysSkip)
    }
  })

  //
  // Validate Contact Us (via Webform module).
  //
  test('(ATK-PW-1050)  Contact Us form accepts input, sends email. @ATK-PW-1050 @contact-us @smoke @alters-db', async ({ page, context }) => {
    const testId = 'ATK-PW-1050'
    const uniqueToken = atkUtilities.createRandomString(6)
    const subjectLine = `${testId} ${uniqueToken}`

    // Begin Contact us.
    const user = atkUtilities.createRandomUser()
    await page.goto(baseUrl + atkConfig.contactUsUrl)

    await page.getByLabel('Your name').fill(user.userName)
    await page.getByLabel('Your email').fill(user.userEmail)
    await page.getByLabel('Subject').fill(subjectLine)
    await page.getByLabel('Message').fill(testId)
    await page.getByRole('button', { name: 'Send message' }).click()

    // The status box needs a moment to appear.
    await atkCommands.expectMessage(page, 'Your message has been sent.')

    await atkCommands.logInViaForm(page, context, qaUserAccounts.admin)

    await page.goto(`${baseUrl}admin/structure/webform/manage/contact/results/submissions`)

    // Check for presence of random string.
    // Part A passes: the submission appears.
    await expect(page.getByText(subjectLine)).toBeVisible()

    // Check for an email sent to site admin.
    // We don't know the address here (unless we use Reroute Email),
    // so just check the subject.
    await atkCommands.expectEmail(atkConfig.email?.reroute?.address ?? /.*/, subjectLine)
  })

  //
  // Website feedback (via Contact form).
  //
  test.skip('(ATK-PW-1051)  Website feedback form accepts input, sends email. @ATK-PW-1051 @website-feedback @smoke @alters-db', async ({ page }) => {
    const testId = 'ATK-PW-1051'
    const uniqueToken = atkUtilities.createRandomString(6)
    const subjectLine = `${testId}: ${uniqueToken}`

    // Begin Website feedback.
    const user = atkUtilities.createRandomUser()
    await page.goto(baseUrl + atkConfig.feedbackUrl)

    await page.getByLabel('Your name').fill(user.userName)
    await page.getByLabel('Your email address').fill(user.userEmail)
    await page.getByLabel('Subject').fill(subjectLine)
    await page.getByLabel('Message').fill(testId)
    await page.getByRole('button', { name: 'Send message' }).click()

    // The status box needs a moment to appear.
    await atkCommands.expectMessage(page, 'Your message has been sent.')

    // Check for an email sent to site admin.
    await atkCommands.expectEmail(atkConfig.email?.reroute?.address ?? /.*/, subjectLine)
  })
})
