import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  return res.status(200).json({ ok: true, user });
});
