import { describe, expect, it, vi } from 'vitest';
import { addMember, fetchMembers, removeMember } from '../members';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

const member = { id: 11, name: 'bob', email: 'bob@example.com', role: 'tester' as const };

describe('api/members', () => {
  it('fetchMembers возвращает участников вместе с флагом can_manage', async () => {
    apiMock.get.mockResolvedValue({ data: { members: [member], can_manage: true } });

    await expect(fetchMembers(4)).resolves.toEqual({ members: [member], can_manage: true });
    expect(apiMock.get).toHaveBeenCalledWith('/projects/4/members');
  });

  it('addMember отправляет логин и роль', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'added', member } });

    const result = await addMember(4, { name: 'bob', role: 'tester' });

    expect(apiMock.post).toHaveBeenCalledWith('/projects/4/members', {
      name: 'bob',
      role: 'tester',
    });
    expect(result).toEqual({ message: 'added', member });
  });

  it('removeMember удаляет участника по userId', async () => {
    apiMock.delete.mockResolvedValue({});

    await removeMember(4, 11);

    expect(apiMock.delete).toHaveBeenCalledWith('/projects/4/members/11');
  });
});
