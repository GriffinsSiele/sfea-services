from pymongo import MongoClient
import datetime


class ishere_session:
    def __init__(self, mongoserver, mongoport) -> None:
        self.client = MongoClient(mongoserver, mongoport)
        self.db = self.client['i-sphere']
        self.sessions = self.db['sessions']

    def list(self, app: str, active: bool = None, phone: str = None):
        filter = {'app': app}
        if type(active) is bool:
            filter.update({'active': active})

        if phone:
            filter.update({'PHONE': phone})
        ans = []
        for session in self.sessions.find(filter=filter):
            ans.append({
                'id':
                    str(session['_id']),
                'phone':
                    session['PHONE'] if 'PHONE' in session.keys() else None,
                'active':
                    session['active'],
                'lastuse':
                    session['lastuse']
                    if 'lastuse' in session.keys() and type(session['lastuse']) is datetime.datetime else None
            })

        return ans

    def list_apps(self):
        return self.sessions.distinct('app')