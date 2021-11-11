import { prerequisitesBuilder } from '../env';
import { resolveSiteUrl } from '../helpers/utils-helper';

global.siteUrl = resolveSiteUrl();
prerequisitesBuilder().withConnection( true ).build();
