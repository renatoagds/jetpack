import os from 'os';
import rimraf from 'rimraf';
import path from 'path';
import { logAccessLog, logDebugLog } from '../helpers/utils-helper.js';

const DIR = path.join( os.tmpdir(), 'jest_playwright_global_setup' );

export default async function () {
	console.log( 'global teardown' );

	await global.browser.close();
	rimraf.sync( DIR );

	await logDebugLog();
	await logAccessLog();
}
