from src.logic.fieldXML import buildXML


class KeyDBAdapter:
    @staticmethod
    def toKeyDB(data):
        output = []

        for key, rows in data.items():
            for row in rows:
                row_data = []
                for field, value in row.items():
                    if value:
                        row_data.append(buildXML(field, value))
                output.append(row_data)
        return output