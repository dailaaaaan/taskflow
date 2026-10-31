import { expect, test } from '@playwright/test';
import type { CDPSession } from '@playwright/test';

test.use({
    viewport: { width: 412, height: 839 },
    hasTouch: true,
    isMobile: true,
});

/** Playwright can tap, but a long press and a drag need real touch events. */
function finger(cdp: CDPSession) {
    const touch = (
        type: 'touchStart' | 'touchMove' | 'touchEnd',
        touchPoints: { x: number; y: number }[],
    ) => cdp.send('Input.dispatchTouchEvent', { type, touchPoints });

    return {
        down: (x: number, y: number) => touch('touchStart', [{ x, y }]),
        moveTo: (x: number, y: number) => touch('touchMove', [{ x, y }]),
        up: () => touch('touchEnd', []),
    };
}

test('moves a card to another list with a long press and a drag', async ({
    page,
    context,
}) => {
    await page.goto('/login');
    await page.locator('[name=email]').fill('demo@taskflow.test');
    await page.locator('[name=password]').fill('password');
    await page.getByTestId('login-button').click();
    await page.getByRole('link', { name: 'Acme Product Team' }).click();
    await page.getByRole('link', { name: 'Product Roadmap' }).click();

    const lists = page.getByTestId('list-column');
    const title = 'Dragged with a finger';

    await lists.first().getByTestId('add-card-button').click();
    await lists.first().getByTestId('new-card-title').fill(title);
    await lists.first().getByTestId('submit-card-button').click();

    const card = lists.first().getByRole('button', { name: title }).first();

    await expect(card).toBeVisible();
    await card.scrollIntoViewIfNeeded();

    const from = await card.boundingBox();

    if (!from) {
        throw new Error('The card is not on screen.');
    }

    const hand = finger(await context.newCDPSession(page));
    const x = from.x + from.width / 2;
    const y = from.y + from.height / 2;

    await hand.down(x, y);
    // The long press has worked once dnd-kit announces the pick-up.
    await expect(page.locator('[id^=DndLiveRegion]')).toContainText(
        `Picked up card “${title}”`,
    );

    // The next list peeks in at the right edge of the screen.
    for (const step of [60, 120, 180, 240, 300]) {
        await hand.moveTo(Math.min(x + step, 400), y);
    }

    await expect(lists.nth(1).getByText(title)).toBeVisible();
    await hand.up();

    await page.reload();

    await expect(lists.nth(1).getByText(title)).toBeAttached();
    await expect(lists.first().getByText(title)).toHaveCount(0);
});
