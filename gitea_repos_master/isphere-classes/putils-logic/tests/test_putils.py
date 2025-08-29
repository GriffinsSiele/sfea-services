from unittest import IsolatedAsyncioTestCase

from putils_logic import PUtils


class TestPUtils(IsolatedAsyncioTestCase):
    def test_bp(self):
        path = PUtils.bp("1", "2", "3")
        self.assertTrue("1/2/3" in path)

        path = PUtils.bp("abcdef", "..", "12345")
        self.assertTrue("12345" in path)
        self.assertTrue("abcdef" not in path)

    def test_mkdir(self):
        dir_name = "123456"
        PUtils.mkdir(dir_name)
        self.assertTrue(PUtils.is_dir_exists(dir_name))
        self.assertTrue(PUtils.is_empty_dir(dir_name))

        PUtils.delete_dir(dir_name)
        self.assertTrue(not PUtils.is_dir_exists(dir_name))

    def test_mkfile(self):
        filename = "123456.txt"
        PUtils.touch_file(filename)
        self.assertTrue(PUtils.is_file_exists(filename))
        self.assertTrue(PUtils.is_empty_file(filename))

        PUtils.delete_file(filename)
        self.assertTrue(not PUtils.is_file_exists(filename))
